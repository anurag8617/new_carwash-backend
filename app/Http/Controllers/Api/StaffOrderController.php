<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserSubscriptionBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ServiceStartedNotification; // ✅ Import this

class StaffOrderController extends Controller
{
    // 1. Start Service -> Send OTP
    public function startService($id)
    {
        // Load the order with the client and service details
        $order = Order::with(['client', 'service'])->findOrFail($id);
        
        // Ensure only assigned orders can be started
        if ($order->status !== 'assigned') { 
            return response()->json(['message' => 'Order must be assigned to start.'], 400);
        }

        // Generate 4-digit OTP
        $otp = rand(1000, 9999);
        
        $order->update([
            'status' => 'in_progress',
            'otp' => $otp // Storing in the 'otp' column
        ]);

        // ✅ Send Notification to the Client
        if ($order->client) {
            $order->client->notify(new ServiceStartedNotification($otp, $order->service->name, $order->id));
        }

        return response()->json([
            'message' => 'Service Started. OTP sent to client.',
            'otp_debug' => $otp // Keep for testing, remove in production
        ]);
    }

    // 2. Complete Service -> Verify OTP
    public function completeService(Request $request, $id)
    {
        $request->validate(['otp' => 'required|digits:4']);

        $order = Order::findOrFail($id);

        // ✅ Verify against the 'otp' column generated in startService
        if ((string)$order->otp !== (string)$request->otp) {
            return response()->json(['message' => 'Invalid OTP. Ask client for the code sent when service started.'], 400);
        }

        // Handle Subscription Balance Deduction
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
            'otp' => null // Clear OTP for security
        ]);

        return response()->json(['message' => 'Service Completed Successfully!']);
    }
}