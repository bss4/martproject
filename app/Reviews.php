<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Reviews extends Model
{
	use SoftDeletes;
    protected $table = "reviews";
    protected $softDelete = true;
}
