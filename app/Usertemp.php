<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Usertemp extends Model
{
    
    protected $table = 'user_temp';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'phone', 'otp'
    ];
}
