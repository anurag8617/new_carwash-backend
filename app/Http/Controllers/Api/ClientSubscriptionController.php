<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use App\Services\RazorpayService;
use App\Models\UserSubscription;
use App\Models\UserSubscriptionBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientSubscriptionController extends Controller
{
    protected $service;
    protected $razorpay;

    public function __construct(SubscriptionService $service, RazorpayService $razorpay)
    {
        $this->service = $service;
        $this->razorpay = $razorpay;
    }

    // 1. List My Subscriptions
    public function index()
    {
        $subs = UserSubscription::where('user_id', Auth::id())
                    ->with(['plan.vendor', 'plan.services']) 
                    ->orderBy('created_at', 'desc')
                    ->get();
                    
        return response()->json($subs);
    }

    // 2. Initiate Purchase
    public function purchase(Request $request, $planId)
    {
        try {
            $payment = $this->service->initiatePurchase(Auth::user(), $planId);
            return response()->json([
                'order_id' => $payment->razorpay_order_id,
                'amount'   => $payment->amount,
                'key'      => env('RAZORPAY_KEY'),
                'user_details' => Auth::user()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // 3. Verify Payment & Activate
    public function verify(Request $request)
    {
        $request->validate([
            'razorpay_order_id'   => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature'  => 'required',
        ]);

        try {
            $sub = $this->service->verifyAndActivate(Auth::user(), $request->all());
            return response()->json(['message' => 'Subscription Activated!', 'subscription' => $sub]);
        } catch (\Exception $e) {
            Log::error("Subscription Verify Error: " . $e->getMessage());
            return response()->json(['message' => 'Verification failed: ' . $e->getMessage()], 400);
        }
    }

    // 4. Cancel Subscription
    public function cancel(Request $request, $id)
    {
        $user = Auth::user();

        // Fetch Subscription ensuring it belongs to the authenticated user
        // Also eagerly load the 'payment' relationship to get transaction details
        $subscription = UserSubscription::with('payment')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found.'], 404);
        }

        // Validation
        if ($subscription->status === 'cancelled') {
            return response()->json(['message' => 'Subscription is already canceled.'], 400);
        }

        if ($subscription->status !== 'active') {
            return response()->json(['message' => 'Only active subscriptions can be canceled.'], 400);
        }

        DB::beginTransaction();

        try {
            // 1. Attempt Refund if payment info exists
            $refundStatus = "No refund processed";
            
            if ($subscription->payment && $subscription->payment->razorpay_payment_id) {
                // Refund the full amount (or pass specific amount if needed)
                $refund = $this->razorpay->refundPayment(
                    $subscription->payment->razorpay_payment_id,
                    $subscription->payment->amount // Refund full amount recorded in DB
                );

                if ($refund) {
                    $refundStatus = "Payment refunded successfully (ID: " . $refund->id . ")";
                    
                    // Optional: Update payment status to 'failed' or a new 'refunded' status if you add it to enum
                    // $subscription->payment->update(['status' => 'failed']); 
                } else {
                    $refundStatus = "Refund attempt failed (check logs)";
                }
            }

            // 2. Cancel on Razorpay (if using Razorpay Subscriptions API)
            if (!empty($subscription->razorpay_subscription_id)) {
                $this->razorpay->cancelSubscription($subscription->razorpay_subscription_id);
            }

            // 3. Update Database Status
            $subscription->update([
                'status' => 'cancelled',
                'canceled_at' => now(),
                'canceled_by' => 'client',
                'canceled_reason' => $request->input('reason', 'User requested cancellation') . " | " . $refundStatus,
            ]);

            // 4. Forfeit Balance (Reset remaining usage to 0)
            UserSubscriptionBalance::where('user_subscription_id', $subscription->id)
                ->update(['total_qty' => 0, 'used_qty' => 0]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription canceled and refund initiated.',
                'refund_status' => $refundStatus,
                'data' => $subscription
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Subscription Cancel Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Cancellation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}