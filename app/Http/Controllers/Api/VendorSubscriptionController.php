<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VendorSubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\UserSubscription;
use App\Models\Vendor;
use App\Models\UserSubscriptionBalance;
use App\Models\Order;
use App\Notifications\OrderOtpNotification;

class VendorSubscriptionController extends Controller
{
    public function index()
    {
        $vendor = Auth::user()->vendor;
        if (!$vendor) return response()->json(['message' => 'Vendor not found'], 404);

        $plans = $vendor->subscriptionPlans()->with('services')->get();
        return response()->json($plans);
    }

    // ✅ NEW: Fetch a single plan for editing
   public function show($id)
    {
        $vendor = Vendor::where('admin_id', Auth::id())->first();
        if (!$vendor) return response()->json(['message' => 'Vendor not found'], 404);

        $plan = $vendor->subscriptionPlans()->with('services')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $plan
        ]);
    }

    public function store(Request $request)
    {
        $this->validatePlan($request); // Refactored validation

        $vendor = Auth::user()->vendor;
        if (!$vendor) return response()->json(['message' => 'Vendor not found'], 403);

        return DB::transaction(function () use ($vendor, $request) {
            $plan = VendorSubscriptionPlan::create([
                'vendor_id'     => $vendor->id,
                'name'          => $request->name,
                'description'   => $request->description,
                'price'         => $request->price,
                'duration_days' => $request->duration_days,
                'is_active'     => true,
            ]);

            $this->syncServices($plan, $request->services);

            return response()->json(['message' => 'Plan created successfully!', 'plan' => $plan], 201);
        });
    }

    public function subscribers()
    {
        $vendor = Vendor::where('admin_id', Auth::id())->first();
        if (!$vendor) return response()->json(['message' => 'Vendor not found'], 404);

        $planIds = $vendor->subscriptionPlans()->pluck('id');

        $subscribers = UserSubscription::whereIn('plan_id', $planIds)
            ->where('status', 'active')
            // ✅ UPDATED: Include 'balances.service' to see remaining quantity
            ->with(['user', 'plan.services', 'payment', 'balances.service']) 
            ->latest()
            ->get();

        return response()->json([
            'success' => true, 
            'data' => $subscribers
        ]);
    }

    // ✅ NEW METHOD: Create & Assign a Task manually
    public function createTask(Request $request)
    {
        $request->validate([
            'user_subscription_id' => 'required|exists:user_subscriptions,id',
            'service_id'           => 'required|exists:services,id',
            'staff_id'             => 'required|exists:staffs,id',
            'scheduled_time'       => 'required|date',
        ]);

        $vendor = Vendor::where('admin_id', Auth::id())->first();
        
        return DB::transaction(function () use ($request, $vendor) {
            // 1. Find the Balance entry
            $balance = UserSubscriptionBalance::where('user_subscription_id', $request->user_subscription_id)
                        ->where('service_id', $request->service_id)
                        ->first();

            if (!$balance) {
                return response()->json(['message' => 'Service not found in this subscription.'], 404);
            }

            // 2. Check if they have credits left
            if ($balance->used_qty >= $balance->total_qty) {
                return response()->json(['message' => 'No remaining credits for this service.'], 400);
            }

            // 3. Deduct Credit
            $balance->increment('used_qty');

            // 4. Create the Order (Task)
            $otp = rand(1000, 9999);
            
            // Get the subscription to find the client
            $subscription = UserSubscription::find($request->user_subscription_id);

            $order = Order::create([
                'client_id'            => $subscription->user_id,
                'vendor_id'            => $vendor->id,
                'service_id'           => $request->service_id,
                'staff_id'             => $request->staff_id,
                'user_subscription_id' => $subscription->id,
                'scheduled_time'       => $request->scheduled_time,
                'price'                => 0, // Paid via subscription
                'status'               => 'assigned', // Immediately assigned
                'payment_status'       => 'paid',
                'payment_method'       => 'subscription',
                'completion_otp'       => $otp,
                'address'              => $subscription->user->address ?? 'Client Address', // Fallback or fetch specific address
            ]);

            // Optional: Notify Client
            try {
                $subscription->user->notify(new OrderOtpNotification($otp, $balance->service->name, $order->id));
            } catch (\Exception $e) {
                // Ignore notification errors
            }

            return response()->json(['success' => true, 'message' => 'Task assigned successfully!', 'data' => $order]);
        });
    }

    // ✅ NEW: Update an existing plan
    public function update(Request $request, $id)
    {
        $this->validatePlan($request);

        $vendor = Auth::user()->vendor;
        $plan = $vendor->subscriptionPlans()->findOrFail($id);

        return DB::transaction(function () use ($plan, $request) {
            $plan->update([
                'name'          => $request->name,
                'description'   => $request->description,
                'price'         => $request->price,
                'duration_days' => $request->duration_days,
            ]);

            // Detach old services and attach new ones with updated quantities
            $this->syncServices($plan, $request->services);

            return response()->json(['message' => 'Plan updated successfully!', 'plan' => $plan]);
        });
    }

    public function destroy($id) {
        $vendor = Auth::user()->vendor;
        $plan = $vendor->subscriptionPlans()->findOrFail($id); // Security check
        $plan->delete();
        return response()->json(['message' => 'Plan deleted']);
    }

    // Helper: Validation Rules
    private function validatePlan($request) {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:1',
            'duration_days' => 'required|integer',
            'services' => 'required|array|min:1',
            'services.*.id' => 'required|exists:services,id',
            'services.*.quantity' => 'required|integer|min:1',
        ]);
    }

    // Helper: Sync Services
    private function syncServices($plan, $services) {
        $syncData = [];
        foreach ($services as $item) {
            $syncData[$item['id']] = ['quantity' => $item['quantity']];
        }
        $plan->services()->sync($syncData);
    }
}