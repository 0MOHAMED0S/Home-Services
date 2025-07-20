<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreelancerProfile extends Model
{
    protected $fillable = ['freelancer_id', 'name', 'city', 'country', 'freelancer_type', 'category_id','description','path','average_price'];
    public function freelancer()
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function ratings()
{
    return $this->hasMany(Rating::class, 'ratee_id')->where('rated_by', 'user');
}

public function getAverageRatingAttribute()
{
    return $this->ratings()->avg('rating') ?? 0;
}

}
