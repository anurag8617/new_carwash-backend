<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'price',
        'duration_days',
        'service_limit',
        'status',
    ];

    /**
     * Relationship: A Plan belongs to a Vendor.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Relationship: A Plan has many client subscriptions.
     */
    public function subscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }
}