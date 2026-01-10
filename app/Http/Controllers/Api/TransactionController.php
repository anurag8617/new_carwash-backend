<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Vendor;
use App\Models\Order;
use App\Models\ClientSubscription;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * ADMIN: See ALL transactions.
     */
    public function indexAdmin(Request $request)
    {
        $user = $request->user();
        
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payments = Payment::with(['user:id,first_name,last_name,email', 'payable'])
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $payments]);
    }

    /**
     * VENDOR: See only transactions related to their Orders or Plans.
     */
    public function indexVendor(Request $request)
    {
        $user = $request->user();

        // Safety check: Ensure user is logged in
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Find the vendor profile linked to this user
        $vendor = Vendor::where('admin_id', $user->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'Vendor profile not found.'], 404);
        }

        // 1. Get IDs of all Orders belonging to this vendor
        $orderIds = Order::where('vendor_id', $vendor->id)->pluck('id');

        // 2. Get IDs of all Subscriptions (Plans) belonging to this vendor
        $subscriptionIds = ClientSubscription::where('vendor_id', $vendor->id)->pluck('id');

        // 3. Fetch Payments matching Orders OR Subscriptions
        $payments = Payment::with(['user:id,first_name,last_name,email', 'payable'])
            ->where(function($query) use ($orderIds, $subscriptionIds) {
                // Payments for Orders
                $query->where(function($q) use ($orderIds) {
                    $q->where('payable_type', Order::class)
                      ->whereIn('payable_id', $orderIds);
                })
                // OR Payments for Subscriptions
                ->orWhere(function($q) use ($subscriptionIds) {
                    $q->where('payable_type', ClientSubscription::class)
                      ->whereIn('payable_id', $subscriptionIds);
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $payments]);
    }
}