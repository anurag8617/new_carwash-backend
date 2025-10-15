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
        'scheduled_time',
        'price',
        'status',
        'payment_status',
    ];

    // An Order belongs to a client (who is a User)
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // An Order belongs to a Vendor
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // An Order is for a specific Service
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}