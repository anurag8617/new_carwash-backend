<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use App\Services\RazorpayService;
use App\Models\UserSubscription;
use App\Models\UserSubscriptionBalance;
use App\Notifications\SubscriptionCancelled;
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
        $subscription = UserSubscription::with('payment')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'Subscription not found.'], 404);
        }

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
            $refundedAmount = 0; // Track amount for notification
            
            if ($subscription->payment && $subscription->payment->razorpay_payment_id) {
                // Refund the full amount
                $refund = $this->razorpay->refundPayment(
                    $subscription->payment->razorpay_payment_id,
                    $subscription->payment->amount 
                );

                if ($refund) {
                    $refundStatus = "Payment refunded successfully (ID: " . $refund->id . ")";
                    $refundedAmount = $subscription->payment->amount;
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

            // 4. Forfeit Balance
            UserSubscriptionBalance::where('user_subscription_id', $subscription->id)
                ->update(['total_qty' => 0, 'used_qty' => 0]);

            DB::commit();

            // ✅ TRIGGER NOTIFICATION HERE
            // This sends the notification to the $user (Client)
            $user->notify(new SubscriptionCancelled($refundStatus, $refundedAmount));

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

    public function show($id)
    {
        $sub = UserSubscription::where('user_id', Auth::id())
                    ->where('id', $id)
                    ->with(['plan.vendor', 'balances.service', 'payment'])
                    ->firstOrFail();
                    
        return response()->json($sub);
    }

    // Delete history
    public function destroy($id)
    {
        $sub = UserSubscription::where('user_id', Auth::id())
                    ->where('id', $id)
                    ->firstOrFail();

        if ($sub->status === 'active') {
            return response()->json(['message' => 'Cannot delete an active subscription.'], 403);
        }

        $sub->delete(); // Permanently remove from history
        return response()->json(['success' => true, 'message' => 'Subscription removed from history.']);
    }
}