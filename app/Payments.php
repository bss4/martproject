<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{

    protected $table = "payments";

    public function users()
    {
    	return $this->belongsTo(User::class,'user_id','id');
    }

    public function sellers()
    {
    	return $this->belongsTo(Sellers::class,'seller_id','id');
    }
}
