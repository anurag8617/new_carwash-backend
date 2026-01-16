<?php

namespace App\Services;

use Razorpay\Api\Api;
use Exception;

class RazorpayService
{
    protected $api;

    public function __construct()
    {
        // Ensure RAZORPAY_KEY and RAZORPAY_SECRET are in your .env
        // $this->api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
        $this->api = new Api(config('razorpay.key_id'), config('razorpay.key_secret'));
    }

    public function createOrder($amount, $receiptId)
    {
        return $this->api->order->create([
            'receipt'         => $receiptId,
            'amount'          => $amount * 100, // Convert to paise
            'currency'        => 'INR',
            'payment_capture' => 1
        ]);
    }

    public function verifySignature($attributes)
    {
        try {
            $this->api->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function cancelSubscription($subscriptionId)
    {
        try {
            // Fetch the subscription
            $subscription = $this->api->subscription->fetch($subscriptionId);
            
            // Cancel it (cancel_at_cycle_end = 0 means immediate)
            return $subscription->cancel(['cancel_at_cycle_end' => 0]);
        } catch (\Exception $e) {
            // Log the error but allows the app to proceed if necessary
            \Log::error("Razorpay Subscription Cancel Failed: " . $e->getMessage());
            // Depending on strictness, you might want to re-throw this
            // throw $e; 
            return null;
        }
    }

    /**
     * Refund a payment.
     *
     * @param string $paymentId
     * @param float|null $amount (Optional) Amount to refund. If null, fully refunded.
     * @return mixed
     */
    public function refundPayment($paymentId, $amount = null)
    {
        try {
            $options = [];
            
            // If specific amount is provided, convert to paise
            if ($amount !== null) {
                $options['amount'] = $amount * 100;
            }

            // Fetch payment and refund
            return $this->api->payment->fetch($paymentId)->refund($options);
        } catch (Exception $e) {
            Log::error("Razorpay Refund Failed: " . $e->getMessage());
            return null;
        }
    }
}