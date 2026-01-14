<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientSubscriptionController extends Controller
{
    protected $service;

    public function __construct(SubscriptionService $service)
    {
        $this->service = $service;
    }

    // 1. List My Subscriptions
    public function index()
    {
        // Eager load 'plan.vendor' so the frontend can show the Vendor Name
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
            // Log the error for debugging
            \Illuminate\Support\Facades\Log::error("Subscription Verify Error: " . $e->getMessage());
            return response()->json(['message' => 'Verification failed: ' . $e->getMessage()], 400);
        }
    }
}