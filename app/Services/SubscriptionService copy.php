<?php

namespace App\Services;

// ✅ MAKE SURE THESE IMPORTS ARE PRESENT
use App\Models\VendorSubscriptionPlan;
use App\Models\SubscriptionPayment;
use App\Models\UserSubscription; 
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class SubscriptionService
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    // Vendor: Create Plan
    public function createPlan($vendorId, array $data)
    {
        return DB::transaction(function () use ($vendorId, $data) {
            $plan = VendorSubscriptionPlan::create([
                'vendor_id'     => $vendorId,
                'name'          => $data['name'],
                'description'   => $data['description'] ?? null,
                'price'         => $data['price'],
                'duration_days' => $data['duration_days'],
            ]);

            // Sync services with quantity
            if (!empty($data['services'])) {
                $syncData = [];
                foreach ($data['services'] as $item) {
                     $syncData[$item['id']] = ['quantity' => $item['quantity']];
                }
                $plan->services()->sync($syncData);
            } else if (!empty($data['service_ids'])) {
                // Fallback for simple ID array
                $plan->services()->sync($data['service_ids']);
            }

            return $plan->load('services');
        });
    }

    // Client: Initiate Purchase
    public function initiatePurchase($user, $planId)
    {
        $plan = VendorSubscriptionPlan::findOrFail($planId);
        
        $orderData = $this->razorpayService->createOrder(
            $plan->price, 
            'sub_rcpt_' . $user->id . '_' . time()
        );

        return SubscriptionPayment::create([
            'user_id'           => $user->id,
            'plan_id'           => $plan->id,
            'razorpay_order_id' => $orderData['id'],
            'amount'            => $plan->price,
            'status'            => 'pending'
        ]);
    }

    // Client: Verify & Activate
    public function verifyAndActivate($user, array $data)
    {
        // 1. Verify Signature
        $isValid = $this->razorpayService->verifySignature([
            'razorpay_order_id'   => $data['razorpay_order_id'],
            'razorpay_payment_id' => $data['razorpay_payment_id'],
            'razorpay_signature'  => $data['razorpay_signature']
        ]);

        if (!$isValid) {
            throw new Exception("Invalid Razorpay Signature");
        }

        return DB::transaction(function () use ($user, $data) {
            // 2. Update Payment Record
            $payment = SubscriptionPayment::where('razorpay_order_id', $data['razorpay_order_id'])->firstOrFail();
            
            $payment->update([
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature'  => $data['razorpay_signature'],
                'status'              => 'paid'
            ]);

            // 3. Create Active Subscription
            $plan = VendorSubscriptionPlan::findOrFail($payment->plan_id);
            
            return UserSubscription::create([
                'user_id'    => $user->id,
                'plan_id'    => $plan->id,
                'payment_id' => $payment->id,
                'start_date' => Carbon::now(), // ✅ Requires 'use Carbon\Carbon'
                'end_date'   => Carbon::now()->addDays($plan->duration_days),
                'status'     => 'active'
            ]);
            // ✅ NEW: Snapshot the Service Quantities
            foreach ($plan->services as $service) {
                // Get quantity from pivot, default to 1 if missing
                $qty = $service->pivot->quantity ?? 1;
                
                \App\Models\UserSubscriptionBalance::create([
                    'user_subscription_id' => $userSub->id,
                    'service_id' => $service->id,
                    'total_qty' => $qty,
                    'used_qty' => 0
                ]);
            }
            return $userSub;
        });
    }

}