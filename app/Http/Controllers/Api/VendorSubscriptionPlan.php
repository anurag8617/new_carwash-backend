<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorSubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    // Relationship: Plan belongs to a Vendor
    public function vendor() {
        return $this->belongsTo(Vendor::class);
    }

    // Relationship: Plan includes multiple Services
    public function services() {
        return $this->belongsToMany(Service::class, 'subscription_plan_services', 'plan_id', 'service_id');
    }
}