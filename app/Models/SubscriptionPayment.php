<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to the Plan being purchased
    public function plan()
    {
        return $this->belongsTo(VendorSubscriptionPlan::class, 'plan_id');
    }
}