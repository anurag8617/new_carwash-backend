<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staffs'; // Ensure table name is correct

    protected $fillable = [
        'user_id',
        'vendor_id',
        'phone',         
        'address',      
        'joining_date', 
        'status',
        'salary',       
        'profile_image', 
        'designation',        
        'id_proof_image',     
        'emergency_contact',  
    ];  

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    
    public function ratings()
    {
        return $this->hasMany(Rating::class, 'staff_id');
    }

    // Helper to get average rating
    public function getAverageRatingAttribute()
    {
        return $this->ratings()->avg('staff_rating') ?? 0;
    }
}