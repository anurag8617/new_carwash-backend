<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserSubscriptionBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ServiceStartedNotification;
use App\Notifications\PaymentCollectedNotification; // ✅ Assume you created this notification


class StaffOrderController extends Controller
{
    public function startService($id)
    {
        $order = Order::with(['client', 'service'])->findOrFail($id);
        
        if ($order->status !== 'assigned') { 
            return response()->json(['message' => 'Order must be assigned to start.'], 400);
        }

        $otp = rand(1000, 9999);
        
        $order->update([
            'status' => 'in_progress',
            'otp' => $otp
        ]);

        if ($order->client) {
            $order->client->notify(new ServiceStartedNotification($otp, $order->service->name, $order->id));
        }

        return response()->json([
            'message' => 'Service Started. OTP sent to client.',
            'otp_debug' => $otp 
        ]);
    }

    public function confirmPayment(Request $request, $id)
    {
        $order = Order::with('vendor.admin')->findOrFail($id);

        // Ensure Order belongs to this staff
        $user = $request->user();
        if ($order->staff && $order->staff->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Payment already collected.'], 400);
        }

        // Update Payment Status
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now()
        ]);

        // Notify Vendor
        if ($order->vendor && $order->vendor->admin) {
            try {
                $order->vendor->admin->notify(new PaymentCollectedNotification($order));
            } catch (\Exception $e) {
                // Log error
            }
        }

        return response()->json(['message' => 'Payment collected successfully!', 'data' => $order]);
    }

    public function completeService(Request $request, $id)
    {
        $request->validate(['otp' => 'required']);

        $order = Order::with('vendor.admin')->findOrFail($id);

        if ((string)$order->otp !== (string)$request->otp) {
            return response()->json(['message' => 'Invalid OTP. Ask client for the code.'], 400);
        }

        // ✅ Payment Logic
        if ($order->payment_method === 'cod' && $order->payment_status === 'unpaid') {
            if (!$request->boolean('payment_collected')) {
                return response()->json(['message' => 'Please collect payment first.'], 422);
            }
            $order->payment_status = 'paid';
            $order->paid_at = now();


            // ✅ NOTIFY VENDOR: Inform them that staff collected cash
            if ($order->vendor && $order->vendor->admin) {
                // Ensure you create this Notification class: php artisan make:notification PaymentCollectedNotification
                try {
                    $order->vendor->admin->notify(new PaymentCollectedNotification($order));
                } catch (\Exception $e) {
                    // Log error but don't fail the request
                    \Log::error("Failed to notify vendor: " . $e->getMessage());
                }
            }
        }

        if ($order->user_subscription_id) {
            $balance = UserSubscriptionBalance::where('user_subscription_id', $order->user_subscription_id)
                        ->where('service_id', $order->service_id)
                        ->first();
            
            if ($balance) {
                $balance->increment('used_qty');
            }
        }

        $order->update([
            'status' => 'completed',
            'otp' => null
        ]);

        return response()->json(['message' => 'Service Completed Successfully!']);
    }
}