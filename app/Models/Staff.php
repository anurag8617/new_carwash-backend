<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;
    
    protected $table = 'staffs';

    protected $fillable = [
        'user_id',
        'vendor_id',
        'status',
    ];

    // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the vendor that the staff belongs to.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}