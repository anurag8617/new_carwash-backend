<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\VendorSubscriptionPlan;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'name',
        'image',
        'banner',
        'description',
        'address',
        'phone',
        'website',
        'location_lat',
        'location_lng',
        'fee_percentage',
        'average_rating',
        'review_count',
        'is_active',
        'opening_time',
        'closing_time',
        'operating_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'location_lat' => 'float',
        'location_lng' => 'float',
        'fee_percentage' => 'float',
        'average_rating' => 'float',
        'operating_days' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
    
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    // ✅ FIX FOR CLIENT SIDE (Used by VendorController)
    public function plans()
    {
        // We must use VendorSubscriptionPlan because the controller checks for 'is_active'
        return $this->hasMany(VendorSubscriptionPlan::class, 'vendor_id');
    }

    // ✅ FIX FOR ADMIN/VENDOR SIDE (Used by VendorSubscriptionController)
    public function subscriptionPlans()
    {
        // The VendorSubscriptionController explicitly calls ->subscriptionPlans()
        return $this->hasMany(VendorSubscriptionPlan::class, 'vendor_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
}