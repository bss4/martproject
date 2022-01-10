<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Shopworkinghours extends Model
{
	use SoftDeletes;
    protected $table = "shop_working_hours";
    protected $softDelete = true;
}
