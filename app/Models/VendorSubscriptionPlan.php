<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorSubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    public function services() {
        // ✅ NEW: Include the 'quantity' field from the pivot table
        return $this->belongsToMany(Service::class, 'subscription_plan_services', 'plan_id', 'service_id')
                    ->withPivot('quantity');
    }

    // ✅ NEW METHOD: Fetch all active subscribers for this vendor
    public function subscribers()
    {
        $vendor = Auth::user()->vendor;
        if (!$vendor) return response()->json(['message' => 'Vendor not found'], 404);

        // 1. Get IDs of all plans belonging to this vendor
        $planIds = $vendor->subscriptionPlans()->pluck('id');

        // 2. Fetch UserSubscriptions for these plans
        // Eager load: User (client), Plan, and the Payment details
        $subscribers = UserSubscription::whereIn('plan_id', $planIds)
            ->where('status', 'active')
            ->with(['user', 'plan', 'payment']) 
            ->latest()
            ->get();

        return response()->json([
            'success' => true, 
            'data' => $subscribers
        ]);
    }
}