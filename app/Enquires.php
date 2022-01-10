<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Enquires extends Model
{
	use SoftDeletes;
    protected $table = "enquires";
    protected $softDelete = true;
}
