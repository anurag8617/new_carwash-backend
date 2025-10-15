<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    // The table name is ratings_and_reviews, not "ratings"
    protected $table = 'ratings_and_reviews';

    protected $fillable = [
        'order_id',
        'client_id',
        'vendor_id',
        'rating',
        'review_text',
    ];
}