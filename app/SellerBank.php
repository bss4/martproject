<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SellerBank extends Model
{
	use SoftDeletes;
    protected $table = "seller_bank_details";
    protected $softDelete = true;
}
