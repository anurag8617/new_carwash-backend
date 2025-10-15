<?php

namespace App\Models; // <-- The corrected line

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'name',
        'description',
        'address',
        'location_lat',
        'location_lng',
        'fee_percentage',
    ];

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function plans()
    {
        return $this->hasMany(Plan::class);
    }
}