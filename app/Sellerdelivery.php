<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Sellerdelivery extends Model
{
	use SoftDeletes;
    protected $table = "seller_delivery";
    protected $softDelete = true;

}
