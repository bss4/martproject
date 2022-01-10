<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class Cart extends Model
{

    protected $table = 'cart';
    
    public function product()
    {
    	return $this->belongsTo('App\Products','product_id','id');
    }

    public function productsvariations()
    {
    	return $this->belongsTo('App\Productsvariations','product_variation_id','id');
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
