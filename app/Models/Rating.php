<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'order_id',
        'rated_by',
        'rater_id',
        'ratee_id',
        'rating',
        'review',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
