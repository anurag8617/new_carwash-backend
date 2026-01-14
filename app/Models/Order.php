<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'vendor_id',
        'service_id',
        'staff_id',
        'scheduled_time',
        'price',
        'status',
        'payment_status',
        'razorpay_order_id',
        'payment_method',
        'paid_at',
        'completion_otp', 
        'before_image',
        'after_image',
        'completion_otp',
        'address',
        'city',
        'pincode',
        'latitude',
        'longitude',
        'user_subscription_id',
        'otp',
        'status',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function rating()
    {
        return $this->hasOne(Rating::class);
    }

    public function userSubscription()
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }
}