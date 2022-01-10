<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SellerStaff extends Model
{
	use SoftDeletes;
    protected $table = "seller_staff";
    protected $softDelete = true;
}
