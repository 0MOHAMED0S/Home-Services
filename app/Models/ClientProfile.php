<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{

    protected $fillable = ['user_id', 'name', 'city', 'country', 'path'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function ratings()
{
    return $this->hasMany(Rating::class, 'ratee_id')->where('rated_by', 'freelancer');
}

public function getAverageRatingAttribute()
{
    return $this->ratings()->avg('rating') ?? 0;
}

}
