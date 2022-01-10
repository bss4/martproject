<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password', 'role','provider', 'provider_id'];

    protected $hidden = ['password', 'remember_token'];

    public function isAdmin() {
       return $this->role === '1';
    }

    public function isUser() {
       return $this->role === '2';
    }

    public function affiliateseller()
    {
        return $this->hasMany('App\Sellers','refferal_by','affiliate_id');
    }
}