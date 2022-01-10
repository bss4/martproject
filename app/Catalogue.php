<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Catalogue extends Model
{
	use SoftDeletes;
    protected $table = "catalogue";
    protected $softDelete = true;

    public function sellers()
    {
    	return $this->belongsTo('App\Sellers','seller_id','id');
    }

    public function category()
    {
    	return $this->belongsTo('App\Category','category_id','id');
    }


    public function catalogueattributes()
    {
        return $this->hasMany('App\CatalogueAttributes','catalogue_id');
    }

    public function catalougevariations()
    {
        return $this->hasMany('App\CatalougeVariations','catalouge_id');
    }
}
