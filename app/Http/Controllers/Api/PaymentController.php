<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Payment;
use App\Models\ClientSubscription;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Exception\SignatureVerificationError;
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

        // Force Integer Cast (Critical for Razorpay)
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

            // ✅ CRITICAL FIX: Save the Razorpay Order ID to the Order table
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
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);

        // Check for existing active subscription
        $exists = ClientSubscription::where('client_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'You already have an active subscription for this plan.'], 409);
        }

        // 1. Create Razorpay Order
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

        // 2. CREATE DATABASE RECORD
        $subscription = ClientSubscription::create([
            'client_id' => $user->id,
            'plan_id' => $plan->id,
            'vendor_id' => $plan->vendor_id, 
            'start_date' => null, 
            'end_date' => null,
            'remaining_services' => $plan->service_limit,
            'status' => 'inactive', 
            'payment_status' => 'pending',
            'razorpay_order_id' => $razorpayOrder['id'] // Saving correctly here
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'razorpay_order_id' => $razorpayOrder['id'],
                'subscription_id' => $subscription->id,
                'amount' => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency'],
                'key_id' => config('razorpay.key_id'),
                'user' => [
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone
                ]
            ]
        ]);
    }

    // --- 3. VERIFY PAYMENT (Handles BOTH Orders & Subscriptions) ---
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
            
            // 1. Verify Signature
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $validated['razorpay_order_id'],
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_signature'  => $validated['razorpay_signature']
            ]);

            $rzpPayment = $api->payment->fetch($validated['razorpay_payment_id']);
            $amount = $rzpPayment->amount / 100;

            // 2. CHECK IF IT IS AN ORDER
            $order = Order::where('razorpay_order_id', $validated['razorpay_order_id'])->first();

            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now()
                ]);

                Payment::create([
                    'user_id' => $request->user()->id,
                    'order_id' => $order->id,        // Has order_id
                    'payable_id' => $order->id,
                    'payable_type' => Order::class,
                    'amount' => $amount,
                    'razorpay_payment_id' => $validated['razorpay_payment_id'],
                    'razorpay_order_id' => $validated['razorpay_order_id'],
                    'status' => 'captured',
                    'payment_method' => $rzpPayment->method ?? 'card'
                ]);

                return response()->json(['success' => true, 'message' => 'Order payment verified.']);
            }

            // 3. CHECK IF IT IS A SUBSCRIPTION
            $subscription = ClientSubscription::where('razorpay_order_id', $validated['razorpay_order_id'])->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'active',
                    'payment_status' => 'paid',
                    'razorpay_payment_id' => $validated['razorpay_payment_id'],
                    'razorpay_signature' => $validated['razorpay_signature'],
                    'start_date' => Carbon::now(),
                    'end_date' => Carbon::now()->addDays($subscription->plan->duration_days ?? 30),
                ]);

                Payment::create([
                    'user_id' => $request->user()->id,
                    'order_id' => null,             // NULL for subscriptions
                    'payable_id' => $subscription->id,
                    'payable_type' => ClientSubscription::class,
                    'amount' => $amount,
                    'razorpay_payment_id' => $validated['razorpay_payment_id'],
                    'razorpay_order_id' => $validated['razorpay_order_id'],
                    'status' => 'captured',
                    'payment_method' => $rzpPayment->method ?? 'card'
                ]);

                return response()->json(['success' => true, 'message' => 'Subscription activated!']);
            }

            return response()->json(['success' => false, 'message' => 'Invalid Razorpay Order ID.'], 404);

        } catch (\Exception $e) {
            Log::error("Verification Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()], 500);
        }
    }
}