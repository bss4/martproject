<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Transfermoney extends Model
{
    use SoftDeletes;
    protected $table = "transfermoney";
    protected $softDelete = true;
}
