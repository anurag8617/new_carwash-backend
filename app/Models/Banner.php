<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image',
        'link',
        'position',   // New
        'is_premium', // New
        'priority',   // New
        'status',     // Replaces is_active
        'start_date', // New
        'end_date',   // New
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];
}