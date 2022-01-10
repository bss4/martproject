<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalougeVariations extends Model
{
	use SoftDeletes;
    protected $table = "catalouge_variations";
    protected $fillable = ['id','seller_id','catalogue_id','attr_name','attr_value','created_at','updated_at'];
    protected $softDelete = true;
}
