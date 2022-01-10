<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Applycoupon extends Model
{
	use SoftDeletes;
    protected $table = "apply_coupon";
    protected $softDelete = true;

}
