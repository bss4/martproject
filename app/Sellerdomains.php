<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Sellerdomains extends Model
{
	use SoftDeletes;
    protected $table = "seller_domians";
    protected $softDelete = true;

}
