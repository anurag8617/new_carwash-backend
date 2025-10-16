<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Exception\SignatureVerificationError;

class PaymentController extends Controller
{
    private $razorpayApi;

    public function __construct()
    {
        // Initialize the Razorpay API client with keys from your config
        $this->razorpayApi = new Api(config('razorpay.key_id'), config('razorpay.key_secret'));
    }

    /**
     * Step 1: Create a Razorpay Order for a given application order.
     */
    public function initiatePayment(Request $request)
    {
        $validatedData = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $client = $request->user();
        $order = Order::findOrFail($validatedData['order_id']);

        // Security check: ensure the user owns the order they are trying to pay for
        if ($client->id !== $order->client_id) {
            return response()->json(['message' => 'Not authorized to perform this action.'], 403);
        }
        
        // Prepare the order data for Razorpay
        $razorpayOrderData = [
            'receipt'         => $order->id,
            'amount'          => $order->price * 100, // Amount in the smallest currency unit (e.g., paise)
            'currency'        => 'INR', // Or your desired currency
            'notes'           => [
                'order_id' => (string)$order->id,
                'client_email' => $client->email,
            ]
        ];

        try {
            $razorpayOrder = $this->razorpayApi->order->create($razorpayOrderData);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error creating Razorpay order.', 'error' => $e->getMessage()], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Payment order created.',
            'data' => [
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency'],
                'key_id' => config('razorpay.key_id'), // Send key to front-end
                'user' => [
                    'name' => $client->first_name . ' ' . $client->last_name,
                    'email' => $client->email,
                    'phone' => $client->phone
                ]
            ]
        ]);
    }

    /**
     * Step 2: Verify the payment signature after it's completed on the front-end.
     */
    public function verifyPayment(Request $request)
    {
        $validatedData = $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'order_id' => 'required|exists:orders,id' // The original order ID from your app
        ]);

        try {
            // This is the core verification logic from the Razorpay SDK
            $this->razorpayApi->utility->verifyPaymentSignature([
                'razorpay_order_id' => $validatedData['razorpay_order_id'],
                'razorpay_payment_id' => $validatedData['razorpay_payment_id'],
                'razorpay_signature' => $validatedData['razorpay_signature']
            ]);

            // If verification is successful, update the order in your database
            $order = Order::findOrFail($validatedData['order_id']);
            $order->payment_status = 'paid';
            $order->razorpay_payment_id = $validatedData['razorpay_payment_id']; // Save for reference
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment successful and verified.'
            ]);

        } catch (SignatureVerificationError $e) {
            // If the signature is not valid, the payment is fraudulent
            return response()->json(['success' => false, 'message' => 'Payment verification failed.'], 400);
        }
    }

    /**
 * Create a Razorpay Order for a given subscription plan.
 */
    public function initiateSubscriptionPayment(Request $request)
    {
        $validatedData = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $client = $request->user();
        $plan = Plan::findOrFail($validatedData['plan_id']);

        $razorpayOrderData = [
            'amount'          => $plan->price * 100,
            'currency'        => 'INR',
            'notes'           => [
                'plan_id' => (string)$plan->id,
                'client_email' => $client->email,
            ]
        ];

        try {
            $razorpayOrder = $this->razorpayApi->order->create($razorpayOrderData);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error creating Razorpay order.'], 500);
        }

        // This response is almost the same as the order payment one
        return response()->json([
            'success' => true,
            'message' => 'Payment order created for plan.',
            'data' => [
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $razorpayOrder['amount'],
                // ... include user and key_id as before
            ]
        ]);
    }
    
}