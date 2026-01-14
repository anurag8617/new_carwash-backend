<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Ensure dates are automatically treated as Carbon objects
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function plan()
    {
        return $this->belongsTo(VendorSubscriptionPlan::class, 'plan_id');
    }

    public function payment()
    {
        return $this->belongsTo(SubscriptionPayment::class, 'payment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function balances()
    {
        return $this->hasMany(UserSubscriptionBalance::class);
    }
}