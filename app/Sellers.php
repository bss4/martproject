<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Sellers extends Model
{

	use SoftDeletes;
    public $table = "sellers";
    protected $fillable = [
        'email','provider', 'provider_id',
    ];
    protected $softDelete = true;
    
    public function stores()
    {
    	return $this->belongsTo('App\Stores','id','seller_id');
    }

    public function storetype()
    {
    	return $this->belongsTo('App\StoreType','package_type','id')->select('id','name');
    }

    public function sellerdelivery()
    {
    	return $this->belongsTo('App\Sellerdelivery','id','seller_id');
    }

    public function sellerslider()
    {
        return $this->hasMany('App\Sellerslider','seller_id','id');
    }
}
