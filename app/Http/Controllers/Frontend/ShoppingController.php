<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController as BaseController;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Mail\Mailer;
use App\Sellers;
use App\Orders;
use App\Products;
use App\Productsvariations;
use App\CatalogueAttributes;
use App\Wishlist;
use App\Offers;
use App\Applycoupon;
use App\Payments;
use Razorpay\Api\Api;
use App\Cart;
use App\Catalogue;
use App\Http\Requests;
use Illuminate\Support\Facades\Hash;
use Auth,Blade,Config,Cache,Cookie,DB,File,Mail,mongoDate,Redirect,Response,Session,URL,View,Validator,hasFile;

class ShoppingController extends BaseController
{
    
    public $model   =   'Shoping';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        View::share('modelName',$this->model);
    }

    /**
     * Show Contactlist.
     *
     */
    public function productdetail($shopid)
    {
        $sellerdetails =  $this->shopaccess($shopid);

        return view('frontend.'.$sellerdetails->theme.'.productdetail',compact('shopid'));
    }


    public function singleproductdetail($shopid,$id=0)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        
        $product_data = Products::with('product_image','productsvariations')->find($id);

        $product_like = Products::where('catalogue_id',$product_data->catalogue_id)->get();
        $CatalogueAttributes = CatalogueAttributes::where('catalogue_id',$product_data->catalogue_id)->get();
        return view('frontend.'.$sellerdetails->theme.'.productdetail',compact('shopid','product_data','product_like','CatalogueAttributes'));
    }

    public function shoppingcart($shopid)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        return view('frontend.'.$sellerdetails->theme.'.shoppingcart',compact('shopid'));
        
    }

    public function checkout($shopid)
    {

        $sellerdetails =  $this->shopaccess($shopid);
        return view('frontend.'.$sellerdetails->theme.'.checkout',compact('shopid'));
       
    }

    public function wishlist($shopid)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        
        if(!Auth::check())
        {
            session()->flash('error', 'User must be login!');
            return Redirect::back();
        }else
        {
            $user_id = Auth::user()->id;
            $wishlist = Wishlist::with('product')->where('seller_id',$sellerdetails->id)->where('user_id',$user_id)->get()->toArray();
            
            session()->put('wishlist',$wishlist);
            
            return view('frontend.'.$sellerdetails->theme.'.wishlist',compact('shopid','wishlist'));
        }
        
    }

    public function catalogs($shopid)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        
        return view('frontend.'.$sellerdetails->theme.'.catalogs',compact('shopid'));
        
    }

    public function singlecatalogue($shopid,$id=0)
    {
        
        $sellerdetails =  $this->shopaccess($shopid);
        $product_list = Products::where('catalogue_id',$id)->paginate((int)RECORD_PER_PAGE);
        $cataloguedata = Catalogue::find($id);
        $CatalogueAttributes = CatalogueAttributes::where('catalogue_id',$id)->get()->toArray();
     
        return view('frontend.'.$sellerdetails->theme.'.catalogs',compact('shopid','product_list','cataloguedata','CatalogueAttributes','id'));

    }

    public function catalogueproductlist(Request $request)
    {
        $catalogueid = $request->catalogueid;
        $size = $request->size;
        $shopid = $request->app_id;
        
        $product = Products::where('catalogue_id',$catalogueid);
        
        if($request->minimum_price!='' || $request->maximum_price!='')
        {
            $product->whereBetween('discount_price',[(int)$request->minimum_price,(int)$request->maximum_price]);
        }
      
        $Productsvariations = Productsvariations::select('product_id')->where('catalogue_id',$catalogueid);
        if($request->color)
        {
            $Productsvariations = $Productsvariations->whereIn('attr_value1',$request->color);
        }

        if($request->size)
        {
           $Productsvariations = $Productsvariations->whereIn('attr_value2',$request->size);
            
        }

        $productvar_val = $Productsvariations->groupBy('product_id')->get()->toArray();
        $productvar_val_data = [];
        foreach ($productvar_val as $value) {
           array_push($productvar_val_data,$value['product_id']);
        }
        
        if(!empty($productvar_val_data))
        {
            $product_list = $product->whereIn('id',$productvar_val_data)->orderBy('discount_price','desc')->get(); 
        }else
        {
            $product_list = $product->orderBy('discount_price','desc')->get(); 
        }
        

        
        
        $sellerdetails =  $this->shopaccess($shopid);
       
       return view('frontend.'.$sellerdetails->theme.'.filtercataproduct',compact('product_list','shopid'))->render();
                        
        
    }

     /**
     * Write code on Method
     *
     * @return response()
     */
    public function addToCart(Request $request)
    {

        $product_id = $request->id;
        $product_quantity = $request->quantity;
        $app_id = $request->app_id;
        

        $productdetails = Products::findOrFail($product_id);
        
        $product_type = $productdetails->type;
        $product_price = '';
        if($product_type!='simple')
        {
            $attribute1 = $request->attribute1;
            $attribute2 = $request->attribute2;
            $attribute3 = $request->attribute3;
            $attribute4 = $request->attribute4;

            $Productsvariations = Productsvariations::where('product_id',$product_id);

            if(isset($attribute1) && !empty($attribute1))
            {
               $Productsvariations = $Productsvariations->where('attr_value1',$attribute1); 
            }
            if(isset($attribute2) && !empty($attribute2))
            {
               $Productsvariations = $Productsvariations->where('attr_value2',$attribute2); 
            }
            if(isset($attribute3) && !empty($attribute3))
            {
               $Productsvariations = $Productsvariations->where('attr_value3',$attribute3); 
            }
            if(isset($attribute4) && !empty($attribute4))
            {
               $Productsvariations = $Productsvariations->where('attr_value4',$attribute4); 
            }

            $Productsvariations = $Productsvariations->first();

            $product_price = isset($Productsvariations->discount_price)?$Productsvariations->discount_price:'';
            $product_stock_qty = isset($Productsvariations->product_stock_qty)?$Productsvariations->product_stock_qty:'';
            if(!empty($product_price))
            {
                
                $product_price = $product_price;
                
            }else
            {
                $product_price = $productdetails->discount_price;
            }
            if($product_stock_qty!='')
            {
                
                $product_stock_qty = $product_stock_qty;
                
            }else
            {

                $product_stock_qty = $productdetails->product_stock_qty;
            }
           
        }else
        {
            $product_price = $productdetails->discount_price;
            $product_stock_qty = $productdetails->product_stock_qty;
        }


        //check product quantity
        if($product_stock_qty < $product_quantity && $product_stock_qty!='isAlways')
        {
            return response()->json([
            "status" => "201",
            "data" =>"You can add to cart minimum".$product_stock_qty." Product."
             ]);
        }
         
        //error
        $sellerdetails =  $this->shopaccess($app_id);
        $current_user = isset(Auth::user()->id)?Auth::user()->id:0;

        $session_cart_id = rand(10000,9999).time();
        if(session()->has('cart_session'))
        {
            $cart_session = session()->get('cart_session');
        }
        else
        {
            session()->put('cart_session',$session_cart_id);
           
            if($current_user!=0)
            {
                $cart_data = Cart::where('seller_id',$sellerdetails->id)->where('user_id',$current_user)->first();
                $cart_session = isset($cart_data->session_id)?$cart_data->session_id:$session_cart_id;

            }else
            {
                $cart_session = $session_cart_id;
            }
            
        }

        /*$cart_data = Cart::where('session_id',$cart_session)->get();
        if($cart_data)
        {*/
            

        $cart_data = Cart::where('seller_id',$sellerdetails->id)->where('session_id',$cart_session)->where('product_id',$product_id)->first();

        if($cart_data)
        {
            $quantity = $cart_data->quantity + 1;
            Cart::where('id',$cart_data->id)->update(['product_id'=>$product_id,'quantity'=>$quantity,'price'=>$product_price,'user_id'=>$current_user]);

        }else
        {
            
            $obj                =    new Cart;
            $obj->session_id    =    $cart_session; 
            $obj->seller_id     =    $sellerdetails->id;    
            $obj->user_id       =    $current_user; 
            $obj->product_id    =    $product_id;   
            $obj->product_variation_id = isset($Productsvariations->id)?$Productsvariations->id:0;   
            $obj->quantity      =    isset($product_quantity)?$product_quantity:1; 
            $obj->price         =    $product_price;    
            $obj->save();   

        }
            
        //}
        
        $cart_data = Cart::with('product')->where('session_id',$cart_session)->where('seller_id',$sellerdetails->id)->get()->toArray();
        session()->put('cart',$cart_data);
        //session()->flash('success', 'Product added to cart successfully!');
        return response()->json([
            "status" => "200",
            "data" => $cart_data
        ]);
        //return redirect()->back()->with('success', 'Product added to cart successfully!');
    }
    
    public function addTowishlist(Request $request)
    {
        
        if(Auth::user())
        {
            $user_id = Auth::user()->id;
            $product_id = $request->id;
            $app_id = $request->app_id;
            $sellerdetails =  $this->shopaccess($app_id);

            $wishlistdata = Wishlist::where('seller_id',$sellerdetails->id)->where('seller_id',$sellerdetails->id)->where('product_id',$product_id)->first();

        if(empty($wishlistdata))
        {
            $obj                 = new Wishlist;
            $obj->seller_id      = $sellerdetails->id;
            $obj->user_id        = $user_id;
            $obj->product_id     = $product_id;
            $obj->save();
        }

        $wishlist = Wishlist::where('user_id',$user_id)->where('seller_id',$sellerdetails->id)->get()->toArray();
        session()->put('wishlist',$wishlist);
        //session()->flash('success', 'Product added to wishlist successfully!');
        $wishlist = session('wishlist');
        return response()->json([
            "status" => "200",
            "data" => count($wishlist)
        ]);

        }else
        {
            return response()->json([
            "status" => "200",
            "data" => 0
            ]);
        }

    }

    public function removewishlist(Request $request)
    {
        $user_id = Auth::user()->id;
        $id = $request->id;
        $app_id = $request->app_id;
     
        Wishlist::where('id',$id)->delete();
        
        $sellerdetails =  $this->shopaccess($app_id);
        $wishlist = Wishlist::where('user_id',$user_id)->where('seller_id',$sellerdetails->id)->get()->toArray();
        session()->put('wishlist',$wishlist);
        //session()->flash('success', 'Product removed successfully');
        return response()->json([
            "status" => "200",
            "data" => $wishlist
            ]);
        
    }


    /**
     * Write code on Method
     *
     * @return response()
     */

    public function list_cart(Request $request)
    {
        $shopid = $request->shopid;

        $sellerdetails =  $this->shopaccess($shopid);

        $cart_session = session()->get('cart_session');
        $cart = Cart::with('product','productsvariations')->where('session_id',$cart_session)->where('seller_id',$sellerdetails->id)->get()->toArray();

        session()->put('cart',$cart);
        
        return view('frontend.'.$sellerdetails->theme.'.cartproductlist',compact('shopid','cart'))->render();
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function updatecart(Request $request)
    {
        
        $product_id = $request->product_id;
        $quantity = $request->quantity;
        $shopid = $request->app_id;
        $sellerdetails =  $this->shopaccess($shopid);

        $cart_session = session()->get('cart_session');

        for($i=0;$i<count($product_id);$i++)
        {

            $checkqunatity = $this->checkquantity($product_id[$i],$quantity[$i]);
            
            if($checkqunatity=='process')
            {
                
               Cart::where('session_id',$cart_session)->where('seller_id',$sellerdetails->id)->where('product_id',$product_id[$i])->update(['quantity'=>$quantity[$i]]); 
            }
        }
        

        if($checkqunatity=='process')
        {
            return response()->json([
            "status" => "200",
            ]);
            
        }else
        {
            return response()->json([
                "status" => "201",
                "data" => "Product quantity must be lessthan".$checkqunatity
            ]);
        }
        /*$cart_data = Cart::with('product')->where('session_id',$cart_session)->where('seller_id',$sellerdetails->id)->get()->toArray();

        session()->put('cart',$cart_data);

        return view('frontend.'.$sellerdetails->theme.'.ajaxcartlist',compact('cart_data','shopid'))->render();*/

    }

    public function checkquantity($product_id,$product_quantity)
    {
        $productdetails = Products::findOrFail($product_id);
        
        $product_type = $productdetails->type;
        $product_price = '';
        if($product_type!='simple')
        {
            
            $Productsvariations = Productsvariations::where('product_id',$product_id)->first();

            $product_price = isset($Productsvariations->discount_price)?$Productsvariations->discount_price:'';
            $product_stock_qty = isset($Productsvariations->product_stock_qty)?$Productsvariations->product_stock_qty:'';
            if($product_price!='')
            {
                
                $product_price = $product_price;
                
            }else
            {
                $product_price = $productdetails->discount_price;
            }

            if($product_stock_qty!='')
            {
                
                $product_stock_qty = $product_stock_qty;
                
            }else
            {

                $product_stock_qty = $productdetails->product_stock_qty;
            }
           
        }else
        {
            $product_price = $productdetails->discount_price;
            $product_stock_qty = $productdetails->product_stock_qty;
        }


        //check product quantity
        if($product_stock_qty < $product_quantity && $product_stock_qty!='isAlways')
        {
           return $product_stock_qty;
        }else
        {
           return 'process';
        }
    }
    
    public function listcartPage()
    {

        $shopid = $_POST['shopid'];
        $sellerdetails =  $this->shopaccess($shopid);
        $cart_session = session()->get('cart_session');
        $cart_data = Cart::with('product','productsvariations')->where('session_id',$cart_session)->where('seller_id',$sellerdetails->id)->get()->toArray();
        
        
        return view('frontend.'.$sellerdetails->theme.'.ajaxcartlist',compact('cart_data','shopid'))->render();
    }

    public function listwishlistPage()
    {

        $shopid = $_POST['shopid'];
        $user_id = Auth::user()->id;
        $sellerdetails =  $this->shopaccess($shopid);

        $wishlist = Wishlist::with('product')->where('seller_id',$sellerdetails->id)->where('user_id',$user_id)->get()->toArray();

        session()->put('wishlist',$wishlist);

        return view('frontend.'.$sellerdetails->theme.'.ajaxwishlist',compact('wishlist','shopid'))->render();
    }
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function removecart(Request $request)
    {
        $product_id = $request->product_id;
        $app_id = $request->app_id;
        $sellerdetails =  $this->shopaccess($app_id);

        $cart_session = session()->get('cart_session');

        Cart::where('seller_id',$sellerdetails->id)->where('session_id',$cart_session)->where('product_id',$product_id)->delete();

        $cart_data = Cart::with('product')->where('seller_id',$sellerdetails->id)->where('session_id',$cart_session)->get()->toArray();

        session()->put('cart',$cart_data);
        
        //session()->flash('success', 'Product removed successfully');
        return response()->json([
            "status" => "200",
            "data" => $cart_data
            ]);
        
    }

    public function order($shopid,Request $request)
    {

        if(!Auth::check())
        {
            session()->flash('error', 'User must be login!');
            return Redirect::back();
        }
        //Input items of form
        $input = $request->all();
        if(!isset($input['razorpay_payment_id']) && empty($input['razorpay_payment_id']))
        {
            session()->flash('error', 'Something went wrong!');
            return Redirect::back();
        }
     
        //get API Configuration 
        $api = new Api(config('custom.razor_key'), config('custom.razor_secret'));
        //Fetch payment information by razorpay_payment_id
        $payment = $api->payment->fetch($input['razorpay_payment_id']);

        if(count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount'=>$payment['amount'])); 

            } catch (\Exception $e) {
                return  $e->getMessage();
                \Session::put('error',$e->getMessage());
                return redirect()->back();
            }

            // Do something here for store payment details in database...
        }
        
        $sellerdetails =  $this->shopaccess($shopid);

        $cart = session()->get('cart');
        $coupon_session = session()->get('coupon_session');
        $coupon_session_val = '';
        if($coupon_session)
        {
            $coupon_session_val = serialize($coupon_session);
            
            $apply_coupon = new Applycoupon;
            $apply_coupon->seller_id = $sellerdetails->id;
            $apply_coupon->user_id   = isset(Auth::user()->id)?Auth::user()->id:0;;
            $apply_coupon->coupon_id = $coupon_session->id;
            $apply_coupon->save();

        }

        $orderdata = '';
        $price =0; 
        foreach($cart as $id => $details)
        {
            $price += (isset($details['price'])?$details['price']:1) * $details['quantity'];
        }
       
        $seller =  Sellers::where('id',$sellerdetails->id)->first();

        $amount =  $payment['amount']/100;

        if(!empty($seller) && !empty($cart))
        {
            
            $obj                =        new Orders;
            $obj->seller_id     =        $seller->id;
            $obj->user_id       =        isset(Auth::user()->id)?Auth::user()->id:0;
            $obj->firstname     =        isset($request->first_name)?$request->first_name:'';
            $obj->lastname      =        isset($request->last_name)?$request->last_name:'';
            $obj->company_name  =        isset($request->company_name)?$request->company_name:'';
            $obj->country       =        isset($request->country)?$request->country:'';
            $obj->address_1     =        isset($request->address_1)?$request->address_1:'';
            $obj->address_2     =        isset($request->address_2)?$request->address_2:'';
            $obj->city          =        isset($request->city)?$request->city:'';
            $obj->state         =        isset($request->state)?$request->state:'';
            $obj->postal_code   =        isset($request->postal_code)?$request->postal_code:'';
            $obj->phone         =        isset($request->phone)?$request->phone:'';
            $obj->email         =        isset($request->email)?$request->email:'';
            $obj->order_comments =       isset($request->order_comments)?$request->order_comments:'';
            $obj->cart_value    =        serialize($cart);
            $obj->coupon_value  =        $coupon_session_val;
            $obj->price         =        isset($amount)?$amount:0;
            $obj->save();

            $orderid = $obj->id;


            
            $payment_obj = new Payments;
            $payment_obj->user_id = isset(Auth::user()->id)?Auth::user()->id:0;
            $payment_obj->seller_id = $seller->id;
            $payment_obj->transaction_id = isset($input['razorpay_payment_id'])?$input['razorpay_payment_id']:'';
            $payment_obj->order_id = $orderid;
            $payment_obj->payment_mode = "online";
            $payment_obj->amount = isset($amount)?$amount:0;
            $payment_obj->status = "success";
            $payment_obj->save();


            $orderdata = Orders::find($orderid);
           
            Cart::where('user_id',Auth::user()->id)->delete();
            session()->forget('cart');
            session()->forget('coupon_session');
            session()->flash('success', 'Order successfully!');

        }else
        {
            session()->flash('error', 'Something wrong!');
        }

        return view('frontend.'.$sellerdetails->theme.'.thankyou',compact('shopid','orderdata'));
    }

    public function changeproduct(Request $request)
    {

        $attribute1 = $request->attribute1;
        $attribute2 = $request->attribute2;
        $attribute3 = $request->attribute3;
        $attribute4 = $request->attribute4;
        $productid = $request->productid;


        $Productsvariations = Productsvariations::where('product_id',$productid);
        if(isset($attribute1) && !empty($attribute1))
        {
           $Productsvariations = $Productsvariations->where('attr_value1',$attribute1); 
        }
        if(isset($attribute2) && !empty($attribute2))
        {
           $Productsvariations = $Productsvariations->where('attr_value2',$attribute2); 
        }
        if(isset($attribute3) && !empty($attribute3))
        {
           $Productsvariations = $Productsvariations->where('attr_value3',$attribute3); 
        }
        if(isset($attribute4) && !empty($attribute4))
        {
           $Productsvariations = $Productsvariations->where('attr_value4',$attribute4); 
        }
        $Productsvariations = $Productsvariations->first();

        return response()->json([
            "status" => "200",
            "data" => $Productsvariations
            ]);
    }

    public function ajaxSingleproduct(Request $request)
    {

       $product_id = $request->id;
      
       $productdata = Products::with('seller','productsvariations')->find($product_id);
       
       $CatalogueAttributes = CatalogueAttributes::where('catalogue_id',$productdata['catalogue_id'])->get();

       return response()->json([
            "status" => "200",
            "productdata" => $productdata,
            "CatalogueAttributes" => $CatalogueAttributes
        ]);

       //return json_encode($productdata);
       
    }

    public function applycouponcode(Request $request)
    {
        $coupon_name = $request->couponcode;
        $app_id = $request->app_id;
        $sellerdetails =  $this->shopaccess($app_id);
        $offer_details = Offers::where('seller_id',$sellerdetails->id)->where('code',$coupon_name)->where('valid_date','>=',date('Y-m-d'))->first();
       
        if(empty($offer_details)){

            return response()->json([

                "status" => "201",
                "data" => "Invalid coupon code. Please try again.",
                
            ]);
        }
        $user_id = Auth::user()->id;
        $apply_coupon = Applycoupon::where('seller_id',$sellerdetails->id)->where('user_id',$user_id)->where('coupon_id',$offer_details->id)->first();

        if(!empty($apply_coupon))
        {
            return response()->json([

                "status" => "201",
                "data" => "Coupon code already Apply.",
                
            ]);

        }else
        {

           session()->put('coupon_session',$offer_details);

           return response()->json([

                "status" => "200",
                "data" => "Coupon Added successfully.",
                
            ]);

        }
       
    }

    public function removecouponcode()
    {
      
        session()->forget('coupon_session');
       
        return response()->json([

                "status" => "200",
                "data" => "Coupon Code removed successfully.",
                
            ]);
    }
}
