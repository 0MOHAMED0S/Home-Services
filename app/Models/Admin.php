<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Model
{
    use HasApiTokens;

    protected $fillable = ['name', 'phone', 'password'];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
