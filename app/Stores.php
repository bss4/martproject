<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Stores extends Model
{
	use SoftDeletes;
    protected $table = "stores";
    protected $softDelete = true;


    public function storetype()
    {
    	return $this->belongsTo('App\StoreType','store_type','id')->select('id','name');
    }

    public function shopworkingtime()
    {
    	
    	return $this->hasMany('App\Shopworkinghours','store_id');
    }

    public function seller()
    {
    	return $this->belongsTo('App\Sellers','seller_id','id');
    }
}
