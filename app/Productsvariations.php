<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Productsvariations extends Model
{
	use SoftDeletes;
    protected $table = "product_variations";
    protected $softDelete = true;

  
}
