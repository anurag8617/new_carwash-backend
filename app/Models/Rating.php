<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $table = 'ratings_and_reviews';

    protected $fillable = [
        'order_id',
        'client_id',
        'vendor_id',
        'staff_id',
        'rating',
        'staff_rating',
        'review_text',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    // ✅ ADDED: Missing relationship to Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}