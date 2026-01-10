<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'plan_id',
        'vendor_id',
        'start_date',
        'end_date',
        'remaining_services',
        'status', 
        'razorpay_order_id',
        'razorpay_payment_id', 
        'razorpay_signature',
        'payment_status'
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}