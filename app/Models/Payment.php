<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payable_id',
        'payable_type',
        'amount',
        'razorpay_payment_id',
        'razorpay_order_id',
        'status',
    ];

    /**
     * Get the parent payable model (Order or Plan/Subscription).
     */
    public function payable()
    {
        return $this->morphTo();
    }

    /**
     * The user who made the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}