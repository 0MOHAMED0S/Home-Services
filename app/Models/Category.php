<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'path',
    ];
    public function freelancers()
    {
        return $this->hasMany(FreelancerProfile::class, 'category_id');
    }
}
