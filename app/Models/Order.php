<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'freelancer_id',
        'quoted_price',
        'billing_unit',
        'city',
        'country',
        'status',
        'payment_method',
        'start_date',
        'description',
        ''
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function freelancer()
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function ratings()
{
    return $this->hasMany(Rating::class);
}


}
