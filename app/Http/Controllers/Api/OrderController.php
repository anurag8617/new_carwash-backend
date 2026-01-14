<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use App\Models\Vendor;
use App\Models\Staff; 
use App\Models\ClientSubscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail; 
use App\Mail\OrderOtpMail; 
use Illuminate\Support\Facades\Auth;
use App\Notifications\OrderOtpNotification;
use Illuminate\Support\Facades\Notification;

class OrderController extends Controller
{
    /**
     * Store a newly created order (Client Only)
     */
public function store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'service_id' => 'required|exists:services,id',
            'scheduled_time' => 'required|date',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'payment_method' => 'nullable|string', 
        ]);

        $user = $request->user();
        $service = Service::findOrFail($request->service_id);
        $otp = rand(1000, 9999);

        // Default: User pays the full price
        $finalPrice = $service->price;
        $userSubscriptionId = null;

        // ✅ HANDLE SUBSCRIPTION LOGIC
        if ($request->payment_method === 'subscription') {
            // 1. Find active subscription that has this service AND has quantity remaining
            $balance = UserSubscriptionBalance::whereHas('subscription', function($q) use ($user) {
                            $q->where('user_id', $user->id)->where('status', 'active');
                        })
                        ->where('service_id', $request->service_id)
                        ->whereColumn('used_qty', '<', 'total_qty') // Check if they have credits left
                        ->first();

            if (!$balance) {
                return response()->json(['message' => 'No active subscription balance available for this service.'], 400);
            }

            // 2. Apply Subscription Logic
            $finalPrice = 0; // It's free because they have a subscription
            $userSubscriptionId = $balance->user_subscription_id;

            // 3. IMPORTANT: Deduct 1 credit from their balance
            $balance->increment('used_qty');
        }

        // ✅ CREATE THE ORDER
        $order = Order::create([
            'client_id' => $user->id,
            'vendor_id' => $request->vendor_id,
            'service_id' => $request->service_id,
            'scheduled_time' => $request->scheduled_time,
            'price' => $finalPrice, 
            'status' => 'pending',
            'payment_status' => $finalPrice == 0 ? 'paid' : 'unpaid', // Auto-mark paid if subscription
            'payment_method' => $request->payment_method, 
            'user_subscription_id' => $userSubscriptionId, // ✅ SAVES THE ID AUTOMATICALLY
            'completion_otp' => $otp,
            'address' => $request->address,
            'city' => $request->city ?? null,
            'pincode' => $request->pincode ?? null,
            'latitude' => $request->latitude ?? null,
            'longitude' => $request->longitude ?? null,
        ]);

        // Send OTP Notification
        try {
            $user->notify(new OrderOtpNotification($otp, $service->name, $order->id));
        } catch (\Exception $e) {
            Log::error("Notification failed: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created. Check your Notifications for OTP.',
            'data' => $order,
        ], 201);
    }


    /**
     * Display orders (Handles Client, Vendor, AND Staff).
     */
   public function index(Request $request)
    {
        $user = $request->user();
        $orders = [];

        if ($user->role === 'client') {
            $threeDaysAgo = now()->subDays(3);

            $orders = Order::where('client_id', $user->id)
                ->where(function($q) {
                    $q->where('payment_method', '!=', 'subscription')
                      ->orWhereNull('payment_method');
                })
                ->where(function ($query) use ($threeDaysAgo) {
                    $query->where('status', '!=', 'completed')
                          ->orWhere(function ($q) use ($threeDaysAgo) {
                              $q->where('status', 'completed')
                                ->where('updated_at', '>=', $threeDaysAgo);
                          });
                })
                ->with(['vendor', 'service', 'staff.user', 'rating']) 
                ->latest()
                ->get();
        } 
        elseif ($user->role === 'vendor') {
            $vendor = Vendor::where('admin_id', $user->id)->first();
            if ($vendor) {
                $orders = Order::where('vendor_id', $vendor->id)
                    // ✅ UPDATED: Added 'userSubscription.payment' to get the payment table details
                    ->with(['client', 'service', 'staff.user', 'rating', 'userSubscription.plan', 'userSubscription.payment'])
                    ->latest()
                    ->get();
            }
        } 
        elseif ($user->role === 'staff') {
            $staff = Staff::where('user_id', $user->id)->first();
            if ($staff) {
                $orders = Order::where('staff_id', $staff->id)
                    ->with(['client', 'service', 'rating'])
                    ->latest()
                    ->get();
            } else {
                return response()->json(['message' => 'Staff profile not found.'], 404);
            }
        }

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'client') {
            return response()->json(['success' => true, 'data' => []]);
        }

        $threeDaysAgo = now()->subDays(3);

        $orders = Order::where('client_id', $user->id)
            ->where(function($q) {
                $q->where('payment_method', '!=', 'subscription')
                  ->orWhereNull('payment_method');
            })
            ->where('status', 'completed')
            ->where('updated_at', '<', $threeDaysAgo) 
            ->with(['vendor', 'service', 'staff.user', 'rating']) 
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function completeOrder(Request $request, Order $order)
    {
        $user = $request->user();
        
        $staff = Staff::where('user_id', $user->id)->first();
        if (!$staff || $order->staff_id !== $staff->id) {
            return response()->json(['message' => 'Unauthorized. You are not the assigned staff for this order.'], 403);
        }

        $request->validate(['otp' => 'required']);

        if ((string)$request->otp !== (string)$order->completion_otp) {
            return response()->json(['message' => 'Invalid OTP. Please ask the client for the correct code.'], 422);
        }

        $order->update(['status' => 'completed']);

        return response()->json(['success' => true, 'message' => 'Order completed successfully.', 'data' => $order]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $user = $request->user();
        $authorized = false;

        if ($user->role === 'vendor') {
            $vendor = Vendor::where('admin_id', $user->id)->first();
            if ($vendor && $order->vendor_id === $vendor->id) {
                $authorized = true;
            }
        } 
        elseif ($user->role === 'staff') {
            $staff = Staff::where('user_id', $user->id)->first();
            if ($staff && $order->staff_id === $staff->id) {
                $authorized = true;
            }
        }

        if (!$authorized) {
            return response()->json(['message' => 'Not authorized to update this order.'], 403);
        }

        $validatedData = $request->validate([
            'status' => ['required', Rule::in(['pending', 'assigned', 'in_progress', 'completed', 'cancelled'])],
            'otp'    => 'nullable|string', 
        ]);

        if ($validatedData['status'] === 'completed') {
            if (!empty($order->completion_otp)) {
                if (!$request->otp || (string)$request->otp !== (string)$order->completion_otp) {
                    return response()->json(['message' => 'Invalid OTP. Ask client for the correct code.'], 422);
                }
            }
        }

        $order->update(['status' => $validatedData['status']]);

        return response()->json(['success' => true, 'message' => 'Order status updated successfully.', 'data' => $order]);
    }

    public function assignStaff(Request $request, Order $order)
    {
        $user = $request->user();
        $vendor = Vendor::where('admin_id', $user->id)->first();

        if (!$vendor || $order->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'Not authorized to update this order.'], 403);
        }

        $validatedData = $request->validate([
            'staff_id' => 'required|exists:staffs,id',
        ]);

        $staff = Staff::find($validatedData['staff_id']);
        
        if (!$staff || $staff->vendor_id !== $vendor->id) {
            return response()->json(['message' => 'This staff member does not belong to your vendor profile.'], 422);
        }

        $order->update([
            'staff_id' => $validatedData['staff_id'],
            'status' => 'assigned' 
        ]);

        return response()->json(['success' => true, 'message' => 'Staff assigned to order successfully.', 'data' => $order]);
    }

    public function uploadEvidence(Request $request, $id)
    {
        $user = $request->user();
        
        $staff = Staff::where('user_id', $user->id)->first();
        $order = Order::find($id);

        if (!$order || !$staff || $order->staff_id !== $staff->id) {
            return response()->json(['message' => 'Unauthorized or Order not found.'], 403);
        }

        $request->validate([
            'type' => 'required|in:before,after',
            'image' => 'required|image|max:5120', 
        ]);

        try {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $request->type . '_' . $order->id . '.' . $file->getClientOriginalExtension();
                
                $path = public_path('uploads/orders');
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }

                $file->move($path, $filename);
                $dbPath = 'uploads/orders/' . $filename;

                if ($request->type === 'before') {
                    $order->before_image = $dbPath;
                    if ($order->status == 'assigned') {
                        $order->status = 'in_progress';
                    }
                } else {
                    $order->after_image = $dbPath;
                }
                
                $order->save();

                return response()->json(['success' => true, 'message' => 'Image uploaded.', 'data' => $order]);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server Error: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Upload failed'], 500);
    }
}