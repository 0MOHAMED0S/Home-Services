<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['user_id', 'freelancer_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function userProfile()
    {
        return $this->belongsTo(ClientProfile::class, 'user_id', 'user_id');
    }
    public function freelancer()
    {
        return $this->belongsTo(Freelancer::class);
    }

    public function freelancerProfile()
    {
        return $this->belongsTo(FreelancerProfile::class, 'freelancer_id', 'freelancer_id');
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
