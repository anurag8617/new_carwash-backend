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
        $this->api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
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
}