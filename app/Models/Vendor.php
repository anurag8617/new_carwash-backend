<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    // 1. ALLOW MASS ASSIGNMENT
    protected $fillable = [
        'admin_id',
        'name',
        'image',
        'banner', // ✅ Added banner here
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

    // 2. AUTOMATIC CONVERSION
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

    // ✅ FIX 1: Add the Plans relationship
    public function plans()
    {
        return $this->hasMany(Plan::class);
    }

    // ✅ FIX 2: Add the Ratings relationship
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
}