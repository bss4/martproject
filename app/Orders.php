<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Orders extends Model
{
    use SoftDeletes;
    protected $table = "orders";
    protected $softDelete = true;
    //protected $attributes = ['first_name'];
   

    public function product()
    {
    	return $this->belongsTo(Products::class,'product_id','id');

    }

    public function catalogue()
    {
    	return $this->belongsTo(Catalogue::class,'catalogue_id','id');
    }

    public function sellers()
    {
    	return $this->belongsTo(Sellers::class,'seller_id','id');
    }

    public function users()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

}
