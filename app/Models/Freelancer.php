<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class Freelancer extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable;
    protected $fillable = ['name', 'email', 'phone', 'password', 'google_id','provider','onesignal_id'];
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'onesignal_id'
    ];
    public function profile()
{
    return $this->hasOne(FreelancerProfile::class);
}
}

