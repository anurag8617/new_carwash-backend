<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan; // Make sure this points to VendorSubscriptionPlan if needed
use App\Models\Payment;
use App\Models\UserSubscription; // ✅ CHANGED from ClientSubscription to UserSubscription
use App\Models\UserSubscriptionBalance; // ✅ ADDED
use App\Models\VendorSubscriptionPlan; // ✅ ADDED
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $razorpayApi;

    public function __construct()
    {
        $this->razorpayApi = new Api(config('razorpay.key_id'), config('razorpay.key_secret'));
    }

    // --- 1. ORDER PAYMENT (For Service Bookings) ---
    public function initiatePayment(Request $request)
    {
        $validatedData = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $client = $request->user();
        $order = Order::findOrFail($validatedData['order_id']);

        if ($client->id !== $order->client_id) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $amountInPaise = (int)($order->price * 100);

        $razorpayOrderData = [
            'receipt'         => 'ord_' . $order->id,
            'amount'          => $amountInPaise, 
            'currency'        => 'INR',
            'notes'           => [
                'type' => 'order',
                'order_id' => (string)$order->id,
                'client_id' => (string)$client->id,
            ]
        ];

        try {
            $razorpayOrder = $this->razorpayApi->order->create($razorpayOrderData);
            $order->update(['razorpay_order_id' => $razorpayOrder['id']]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error creating Razorpay order.', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency'],
                'key_id' => config('razorpay.key_id'),
                'user' => [
                    'name' => ($client->first_name ?? '') . ' ' . ($client->last_name ?? ''),
                    'email' => $client->email ?? '',
                    'phone' => $client->phone ?? ''
                ]
            ]
        ]);
    }

    // --- 2. SUBSCRIPTION PAYMENT (For Plans) ---
    public function initiateSubscriptionPayment(Request $request)
    {
        $request->validate(['plan_id' => 'required|exists:vendor_subscription_plans,id']); // ✅ Fixed table check

        $user = $request->user();
        $plan = VendorSubscriptionPlan::findOrFail($request->plan_id);

        // Check for existing active subscription
        $exists = UserSubscription::where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'You already have an active subscription for this plan.'], 409);
        }

        $razorpayOrderData = [
            'receipt'         => 'sub_' . $plan->id . '_' . time(),
            'amount'          => (int)($plan->price * 100),
            'currency'        => 'INR',
            'notes'           => ['plan_id' => (string)$plan->id, 'client_id' => (string)$user->id]
        ];

        try {
            $razorpayOrder = $this->razorpayApi->order->create($razorpayOrderData);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Razorpay Error', 'error' => $e->getMessage()], 500);
        }

        // ✅ UPDATED: Use UserSubscription model
        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            // 'payment_id' is optional here, will be filled on verify
            'start_date' => null, 
            'end_date' => null,
            'status' => 'inactive', 
            // We store the razorpay_order_id to find this record later. 
            // Note: Ensure your UserSubscription table has a 'razorpay_order_id' column or use a temporary table/session.
            // If UserSubscription doesn't have razorpay_order_id, you might need to add it or use the Payment table.
            // Assuming strict schema, let's create a Payment record PENDING now.
        ]);
        
        // Create a pending Payment record to track the Order ID
        Payment::create([
            'user_id' => $user->id,
            'payable_id' => $subscription->id,
            'payable_type' => UserSubscription::class,
            'amount' => $plan->price,
            'razorpay_order_id' => $razorpayOrder['id'],
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency'],
                'key_id' => config('razorpay.key_id'),
                'subscription_id' => $subscription->id
            ]
        ]);
    }

    // --- 3. VERIFY PAYMENT ---
    public function verifyPayment(Request $request)
    {
        Log::info("Verifying Payment: ", $request->all());

        $validated = $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id'   => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        try {
            $api = new Api(config('razorpay.key_id'), config('razorpay.key_secret'));
            
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $validated['razorpay_order_id'],
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_signature'  => $validated['razorpay_signature']
            ]);

            $rzpPayment = $api->payment->fetch($validated['razorpay_payment_id']);
            $amount = $rzpPayment->amount / 100;

            // A. CHECK IF ORDER
            $order = Order::where('razorpay_order_id', $validated['razorpay_order_id'])->first();
            if ($order) {
                $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
                
                // Update or Create Payment Record
                Payment::updateOrCreate(
                    ['razorpay_order_id' => $validated['razorpay_order_id']],
                    [
                        'user_id' => $request->user()->id,
                        'order_id' => $order->id,
                        'payable_id' => $order->id,
                        'payable_type' => Order::class,
                        'amount' => $amount,
                        'razorpay_payment_id' => $validated['razorpay_payment_id'],
                        'status' => 'captured',
                        'payment_method' => $rzpPayment->method ?? 'card'
                    ]
                );
                return response()->json(['success' => true, 'message' => 'Order payment verified.']);
            }

            // B. CHECK IF SUBSCRIPTION (Via Payment Table Look up)
            $pendingPayment = Payment::where('razorpay_order_id', $validated['razorpay_order_id'])
                                     ->where('payable_type', UserSubscription::class)
                                     ->first();

            if ($pendingPayment) {
                $subscription = UserSubscription::find($pendingPayment->payable_id);
                
                if ($subscription) {
                    $subscription->update([
                        'status' => 'active',
                        'payment_id' => $pendingPayment->id, // Link payment
                        'start_date' => Carbon::now(),
                        'end_date' => Carbon::now()->addDays($subscription->plan->duration_days ?? 30),
                    ]);

                    $pendingPayment->update([
                        'status' => 'captured',
                        'razorpay_payment_id' => $validated['razorpay_payment_id'],
                        'payment_method' => $rzpPayment->method ?? 'card'
                    ]);

                    // ✅ CRITICAL FIX: GENERATE BALANCES
                    // This was missing before!
                    if ($subscription->plan && $subscription->plan->services) {
                        foreach ($subscription->plan->services as $service) {
                            UserSubscriptionBalance::create([
                                'user_subscription_id' => $subscription->id,
                                'service_id' => $service->id,
                                'total_qty' => $service->pivot->quantity ?? 1,
                                'used_qty' => 0
                            ]);
                        }
                    }

                    return response()->json(['success' => true, 'message' => 'Subscription activated!']);
                }
            }

            return response()->json(['success' => false, 'message' => 'Invalid Razorpay Order ID.'], 404);

        } catch (\Exception $e) {
            Log::error("Verification Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()], 500);
        }
    }
}