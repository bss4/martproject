<?php
use App\Catalogue;
use App\Sellers;
use App\Wishlist;
use App\Cart;

if(!function_exists("menu")){

    function menu($shopid){

    	$sellerdetails = Sellers::where('app_id',$shopid)->first();
        return $catalogs_list = Catalogue::where('seller_id',$sellerdetails->id)->where('status',CATALOGUE_ACTIVE)->get();
    }
}

if(!function_exists("sellerdetails")){

    function sellerdetails($shopid){

    	return $sellerdetails = Sellers::with('stores')->where('app_id',$shopid)->first();

    }
}

if(!function_exists("cartsession")){

    function cartsession($shopid){

    	$sellerdetails =  Sellers::where('app_id',$shopid)->first();
        
    	$cart_session = session()->get('cart_session');
        $cart_data = Cart::with('product')->where('session_id',$cart_session)->where('seller_id',$sellerdetails->id)->get()->toArray();

        session()->put('cart',$cart_data);

    }
}

if(!function_exists("wishlistsession")){

    function wishlistsession($shopid){

    	$sellerdetails =  Sellers::where('app_id',$shopid)->first();
        if(Auth::user())
        {
        	$wishlist = Wishlist::where('user_id',Auth::user()->id)->where('seller_id',$sellerdetails->id)->get()->toArray();
           session()->put('wishlist',$wishlist);
        }else
        {
        	session()->put('wishlist',[]);
        }
    	

    }
}
