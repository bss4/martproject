<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Purchaseplan extends Model
{
    use SoftDeletes;
    protected $table = "purchase_plan";
    protected $softDelete = true;
}
