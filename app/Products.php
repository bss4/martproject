<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Products extends Model
{
	use SoftDeletes;
    protected $table = "products";
    protected $softDelete = true;

    public function seller()
    {
    	return $this->belongsTo('App\Sellers','seller_id','id');
    }

    public function catalogue()
    {
    	return $this->belongsTo('App\Catalogue','catalogue_id','id');
    }

    public function product_image()
    {
        return $this->hasMany('App\Productsimage','product_id');
    }

    public function productsvariations()
    {
        return $this->hasMany('App\Productsvariations','product_id');
    }
}
