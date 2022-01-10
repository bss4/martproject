<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellerCustomer extends Model
{
	use SoftDeletes;
    protected $table = "seller_customer";
    protected $softDelete = true;
}
