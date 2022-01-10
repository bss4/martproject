<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Sellerslider extends Model
{
	use SoftDeletes;
    protected $table = "seller_slider";
    protected $softDelete = true;
}
