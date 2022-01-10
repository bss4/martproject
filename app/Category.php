<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
	use SoftDeletes;
    protected $table = "category";
    protected $softDelete = true;

    public function storetype()
    {
    	return $this->belongsTo('App\StoreType','id');
    }
}
