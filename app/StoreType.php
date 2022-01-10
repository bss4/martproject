<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class StoreType extends Model
{
	use SoftDeletes;
    protected $table = "store_type";
    protected $softDelete = true;
}
