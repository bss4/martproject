<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;

use App\Sellers;
use App\SellerStaff;
use App\SellerCustomer;
use App\Usertemp;
use App\Stores;
use App\Themes;
use App\StoreType;
use App\Catalogue;
use App\Products;
use App\Category;
use App\Productsimage;
use App\Productsvariations;
use App\CatalogueAttributes;
use App\CatalougeVariations;
use App\Plans;
use App\Payments;
use App\User;
use App\Purchaseplan;
use App\SellerBank;
use App\Reviews;
use App\Offers;
use App\Orders;
use App\Sellerdomains;
use App\Sellerdelivery;
use App\Shopworkinghours;
use App\Productexcelfile;
use App\Sellerslider;
use App\Setting;
use App\Contactus;
use App\Newsletter;
use App\Cart;
use App\SellerUipdetails;
use App\Countries;
use App\States;
use App\Cities;

use Illuminate\Support\Facades\Auth;
use Validator,Socialite,DB;
   
class ApiController extends BaseController
{
    
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
     
 
    public function apiRegister(Request $request)
    {

        if(!empty($request->provider_id))
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'provider' => 'required',
                'provider_id' => 'required',
            ]);

        }else
        {
            $validator = Validator::make($request->all(), [
                'phone' => 'required|numeric|digits:10',
                'device_id' => 'required',
                'fcm_token' => 'required',
            ]);
        }
        
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
   
            if(!empty($request->provider_id))
            {

                $Sellers_details = Sellers::where('provider_id',$request->provider_id)->first();
                if($Sellers_details)
                {
                    $success['flag'] = 'Already exist';
                }else
                {
                    $Sellers_details = Sellers::create([
                            'name'         => $request->name,
                            'provider_id'   => $request->provider_id,
                            'provider'      => $request->provider,
                        ]);

                    $success['flag'] = 'new user register';
                }
                
                
                $success['name'] = $request->name;
                $success['provider_id'] =$request->provider_id;
                $success['provider'] =$request->provider;
                $success['seller_id'] =$Sellers_details->id;
                return $this->sendResponse($success, 'User created Successfully.');

            }else
            {
                         //$otp  = rand(1111, 9999);
                $otp  = 1234;
                $usertemp = Usertemp::where('phone',$request->phone)->first();
                
                if($usertemp)
                {   
                    Usertemp::where('phone',$request->phone)->update(['otp' =>$otp,'device_id'=>$request->device_id,'fcm_token'=>$request->fcm_token]);

                    $success['flag'] = 'Already exist';
                }
                else
                {
                    $userdata = Sellers::where('phone',$request->phone)->first();
                    $SellerStaff = SellerStaff::where('phone',$request->phone)->first();

                    if(empty($userdata) && empty($SellerStaff))
                    {
                        $obj = new Usertemp();
                        $obj->phone =   $request->phone; 
                        $obj->otp = $otp;
                        $obj->device_id = $request->device_id;
                        $obj->fcm_token = $request->fcm_token;
                        $obj->save(); 

                        $success['flag'] = 'new user register';

                    }else if(empty($userdata) && !empty($SellerStaff)){

                    	$Usertemp = Usertemp::where('phone',$request->phone)->first();
                    	if($Usertemp)
                    	{
                    		Usertemp::where('phone',$request->phone)->update(['otp'=>$otp,'device_id'=>$request->device_id,'fcm_token'=>$request->fcm_token]);
                    	}else
                    	{
                    		$obj = new Usertemp;
                    		$obj->phone =   $request->phone; 
	                        $obj->otp = $otp;
	                        $obj->device_id = $request->device_id;
	                        $obj->fcm_token = $request->fcm_token;
	                        $obj->save(); 
                    	}
                        

                        $success['flag'] = 'new seller staff register';

                    }                      
                
                }

                $success['otp'] = $otp;
                $success['phone'] =$request->phone;
                $success['fcm_token'] =$request->fcm_token;
                return $this->sendResponse($success, 'Please verify your otp.');
            }
           
        
    }
    
    function otpVarify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:10',
            'otp'   => 'required',
            'device_id'   => 'required',
            'fcm_token'   => 'required',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        $seller_staff_id='';
        $seller_id='';
        $usertemp = Usertemp::where('phone',$request->phone)->where('otp',$request->otp)->first();
        if($usertemp)
        {
            $userdata = Sellers::where('phone',$request->phone)->first();
            $SellerStaff = SellerStaff::where('phone',$request->phone)->first();

            if(empty($userdata) && empty($SellerStaff))
            {

                $obj            =   new Sellers();
                $obj->phone     =   $request->phone;
                $obj->device_id =   $usertemp->device_id;
                $obj->fcm_token =   $usertemp->fcm_token;
                
                $obj->save();
                $seller_id = $obj->id;

            }
            else if(!empty($SellerStaff) && empty($userdata))
            {
            	
                $seller_staff_id = $SellerStaff->id;
                $staff_seller_id = $SellerStaff->seller_id;
                $permission = $SellerStaff->permission;

            }else if(empty($SellerStaff) && !empty($userdata))
            {
            	$seller_id = $userdata->id;
            }

            //$success['token'] =  $obj->createToken('MyApp')->accessToken;
            

            if($seller_id!='')
            {
            	$success['user_type'] =  'seller';
            	$success['seller_id'] =  isset($seller_id)?$seller_id:'';
            }else
            {
            	$success['user_type'] =  'seller_staff';
            	$success['seller_staff_id'] =  isset($seller_staff_id)?$seller_staff_id:'';
                $success['seller_id'] =  isset($staff_seller_id)?$staff_seller_id:'';
                $success['permission'] =  isset($permission)?$permission:'';
            }
            	
            
            $success['phone']     =  $request->phone;
            $success['fcm_token'] =  $request->fcm_token;
            $success['device_id'] =  $request->device_id;

            return $this->sendResponse($success, 'Otp verified successfully.User register successfully.');
        }else
        {
            return $this->sendError('User can not register.', []);
        }
       

    }
 
 
    /**
     * Seller Store Api Section
     *
     * @Start Seller Store Api Section
    */
    public function sellerdetails(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::with('stores','storetype','sellerslider')->where('id',$request->seller_id)->get();

       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }
        
        $data = [];
        foreach ($user_detail as $value) {

            if(!empty($value->aadhar_card))
            {
                $aadhar_card = DOCUMENT_URL.$value->aadhar_card;
            }else
            {
                $aadhar_card ='';
            }
            if(!empty($value->pan_card))
            {
                $pan_card = DOCUMENT_URL.$value->pan_card;
            }else
            {
                $pan_card ='';
            }
            if(!empty($value->image))
            {
                $profile_image = DOCUMENT_URL.$value->image;
            }else
            {
                $profile_image ='';
            }


            $store_data[] = [
            "id"=> $value->id,
            "seller_id"=> isset($value->stores['seller_id'])?$value->stores['seller_id']:'',
            "store_type"=> isset($value->stores['store_type'])?$value->stores['store_type']:'',
            "business_name"=> isset($value->stores['business_name'])?$value->stores['business_name']:'',
            "name"=> isset($value->stores['name'])?$value->stores['name']:'',
            "logo"=> isset($value->stores['logo'])?STORE_LOGO_URL.$value->stores['logo']:'',
            "city"=> isset($value->stores['city'])?$value->stores['city']:'',
            "state"=> isset($value->stores['state'])?$value->stores['state']:'',
            "country"=> isset($value->stores['country'])?$value->stores['country']:'',
            "pin_code"=> isset($value->stores['pin_code'])?$value->stores['pin_code']:'',
            "address"=> isset($value->stores['address'])?$value->stores['address']:'',
            "theme_color"=> isset($value->stores['theme_color'])?$value->stores['theme_color']:'',
            "tag_line"=> isset($value->stores['tag_line'])?$value->stores['tag_line']:'',
            "slideshow"=> isset($value->stores['slideshow'])?$value->stores['slideshow']:'',
            "explore_more"=> isset($value->stores['explore_more'])?$value->stores['explore_more']:'',
            "new_arrival"=> isset($value->stores['new_arrival'])?$value->stores['new_arrival']:'',
            "single_product"=> isset($value->stores['single_product'])?$value->stores['single_product']:'',
            "recommended_for_you"=> isset($value->stores['recommended_for_you'])?$value->stores['recommended_for_you']:'',
            "customer_review"=> isset($value->stores['customer_review'])?$value->stores['customer_review']:'',
            "image_action"=> isset($value->stores['image_action'])?$value->stores['image_action']:'',
            "privacy_policy"=> isset($value->stores['privacy_policy'])?$value->stores['privacy_policy']:'',
            "return_refund_policy"=> isset($value->stores['return_refund_policy'])?$value->stores['return_refund_policy']:'',
            "shipping_policy"=> isset($value->stores['shipping_policy'])?$value->stores['shipping_policy']:'',
            "terms_conditions"=> isset($value->stores['terms_conditions'])?$value->stores['terms_conditions']:'',
            "payments_policy"=> isset($value->stores['payments_policy'])?$value->stores['payments_policy']:'',
            "about_us"=> isset($value->stores['about_us'])?$value->stores['about_us']:'',
            "status"=> isset($value->stores['status'])?$value->stores['status']:'',
            "created_at"=> isset($value->stores['created_at'])?$value->stores['created_at']:'',
            "updated_at"=> isset($value->stores['updated_at'])?$value->stores['updated_at']:''];

            $sellerslider = array();
            if(!empty($value->sellerslider))
            {
                foreach($value->sellerslider as $slider_val)
                {
                   
                   array_push($sellerslider,['id'=>$slider_val['id'],'seller_id'=>$slider_val['seller_id'],'image'=>SLIDER_URL.$slider_val['image']]);
                }                
            }

            $plandata = Plans::find($value->package_type);
            if(!empty($plandata))
            {
                $plandata_arr = $plandata;
            }else
            {
                $plandata_arr = [];
            }

            if($value->stores['id'])
            {
                 $Shopworkinghours = Shopworkinghours::where('store_id',$value->stores['id'])->get();
            }else
            {
                $Shopworkinghours = [];
            }
           

            $data[] = [
            "id"=> $value->id,
            "app_id"=> isset($value->app_id)?$value->app_id:'',
            "store_url"=> isset($value->app_id)?url('/').'/'.$value->app_id:'',
            "theme"=> isset($value->theme)?$value->theme:'',
            "name"=> isset($value->name)?$value->name:'',
            "phone"=> isset($value->phone)?$value->phone:'',
            "email"=> isset($value->email)?$value->email:'',
            "social_id"=> isset($value->social_id)?$value->social_id:'',
            "refferal_id"=> isset($value->refferal_id)?$value->refferal_id:'',
            "refferal_by"=> isset($value->refferal_by)?$value->refferal_by:'',
            "address"=> isset($value->address)?$value->address:'',
            "billing_address"=> isset($value->billing_address)?$value->billing_address:'',
            "role"=> isset($value->role)?$value->role:'',
            "aadhar_card"=> $aadhar_card,
            "pan_card"=>$pan_card,
            "image"=> $profile_image,
            "gst"=> isset($value->gst)?$value->gst:'',
            "purchage_plan"=> $plandata_arr,
            "start_package_date"=> isset($value->start_package_date)?$value->start_package_date:'',
            "end_package_date"=> isset($value->end_package_date)?$value->end_package_date:'',
            "status"=> isset($value->status)?$value->status:'',
            "is_verify"=> isset($value->is_verify)?$value->is_verify:'',
            "device_id"=> isset($value->device_id)?$value->device_id:'',
            "fcm_token"=> isset($value->fcm_token)?$value->fcm_token:'',
            "provider"=> isset($value->provider)?$value->provider:'',
            "provider_id"=> isset($value->provider_id)?$value->provider_id:'',
            "facebook_url"=> isset($value->facebook_url)?$value->facebook_url:'',
            "instagram_url"=> isset($value->instagram_url)?$value->instagram_url:'',
            "coin"=> isset($value->coin)?$value->coin:0,
            "created_at"=> isset($value->created_at)?$value->created_at:'',
            "updated_at"=> isset($value->updated_at)?$value->updated_at:'',
            "stores"=>$store_data,
            "storetype"=>$value->storetype,
            "sellerslider"=>$sellerslider,
            "shop_working_time"=>$Shopworkinghours,

            ];
        }
       
    
       return $this->sendResponse($data,'Seller details');
    }


    public function dashboard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
         // check user details
        $user_detail = Sellers::with('stores','storetype','sellerslider')->where('id',$request->seller_id)->get();

	    if(empty($user_detail)){
	            return $this->sendError('User does not exist.', []);
	        }
	        
	        $data = [];
	        foreach ($user_detail as $value) {

	            if(!empty($value->aadhar_card))
	            {
	                $aadhar_card = DOCUMENT_URL.$value->aadhar_card;
	            }else
	            {
	                $aadhar_card ='';
	            }
	            if(!empty($value->pan_card))
	            {
	                $pan_card = DOCUMENT_URL.$value->pan_card;
	            }else
	            {
	                $pan_card ='';
	            }
	            if(!empty($value->image))
	            {
	                $profile_image = DOCUMENT_URL.$value->image;
	            }else
	            {
	                $profile_image ='';
	            }


	            $store_data[] = [
	            "id"=> $value->id,
	            "seller_id"=> isset($value->stores['seller_id'])?$value->stores['seller_id']:'',
	            "store_type"=> isset($value->stores['store_type'])?$value->stores['store_type']:'',
	            "business_name"=> isset($value->stores['business_name'])?$value->stores['business_name']:'',
	            "name"=> isset($value->stores['name'])?$value->stores['name']:'',
	            "logo"=> isset($value->stores['logo'])?STORE_LOGO_URL.$value->stores['logo']:'',
	            "city"=> isset($value->stores['city'])?$value->stores['city']:'',
	            "state"=> isset($value->stores['state'])?$value->stores['state']:'',
	            "country"=> isset($value->stores['country'])?$value->stores['country']:'',
	            "pin_code"=> isset($value->stores['pin_code'])?$value->stores['pin_code']:'',
                "address"=> isset($value->stores['address'])?$value->stores['address']:'',
	            "theme_color"=> isset($value->stores['theme_color'])?$value->stores['theme_color']:'',
	            "tag_line"=> isset($value->stores['tag_line'])?$value->stores['tag_line']:'',
	            "slideshow"=> isset($value->stores['slideshow'])?$value->stores['slideshow']:'',
	            "explore_more"=> isset($value->stores['explore_more'])?$value->stores['explore_more']:'',
	            "new_arrival"=> isset($value->stores['new_arrival'])?$value->stores['new_arrival']:'',
	            "single_product"=> isset($value->stores['single_product'])?$value->stores['single_product']:'',
	            "recommended_for_you"=> isset($value->stores['recommended_for_you'])?$value->stores['recommended_for_you']:'',
	            "customer_review"=> isset($value->stores['customer_review'])?$value->stores['customer_review']:'',
	            "image_action"=> isset($value->stores['image_action'])?$value->stores['image_action']:'',
	            "privacy_policy"=> isset($value->stores['privacy_policy'])?$value->stores['privacy_policy']:'',
	            "return_refund_policy"=> isset($value->stores['return_refund_policy'])?$value->stores['return_refund_policy']:'',
	            "shipping_policy"=> isset($value->stores['shipping_policy'])?$value->stores['shipping_policy']:'',
	            "terms_conditions"=> isset($value->stores['terms_conditions'])?$value->stores['terms_conditions']:'',
	            "payments_policy"=> isset($value->stores['payments_policy'])?$value->stores['payments_policy']:'',
	            "about_us"=> isset($value->stores['about_us'])?$value->stores['about_us']:'',
	            "status"=> isset($value->stores['status'])?$value->stores['status']:'',
	            "created_at"=> isset($value->stores['created_at'])?$value->stores['created_at']:'',
	            "updated_at"=> isset($value->stores['updated_at'])?$value->stores['updated_at']:''];

	            $sellerslider = array();
	            if(!empty($value->sellerslider))
	            {
	                foreach($value->sellerslider as $slider_val)
	                {
	                   
	                   array_push($sellerslider,['id'=>$slider_val['id'],'seller_id'=>$slider_val['seller_id'],'image'=>SLIDER_URL.$slider_val['image']]);
	                }                
	            }


	            $data[] = [
	            "id"=> $value->id,
	            "app_id"=> isset($value->app_id)?$value->app_id:'',
	            "theme"=> isset($value->theme)?$value->theme:'',
	            "name"=> isset($value->name)?$value->name:'',
	            "phone"=> isset($value->phone)?$value->phone:'',
	            "email"=> isset($value->email)?$value->email:'',
	            "social_id"=> isset($value->social_id)?$value->social_id:'',
	            "refferal_id"=> isset($value->refferal_id)?$value->refferal_id:'',
	            "refferal_by"=> isset($value->refferal_by)?$value->refferal_by:'',
	            "address"=> isset($value->address)?$value->address:'',
	            "billing_address"=> isset($value->billing_address)?$value->billing_address:'',
	            "role"=> isset($value->role)?$value->role:'',
	            "aadhar_card"=> $aadhar_card,
	            "pan_card"=>$pan_card,
	            "image"=> $profile_image,
	            "gst"=> isset($value->gst)?$value->gst:'',
	            "package_type"=> isset($value->package_type)?$value->package_type:'',
	            "start_package_date"=> isset($value->start_package_date)?$value->start_package_date:'',
	            "end_package_date"=> isset($value->end_package_date)?$value->end_package_date:'',
	            "status"=> isset($value->status)?$value->status:'',
	            "is_verify"=> isset($value->is_verify)?$value->is_verify:'',
	            "device_id"=> isset($value->device_id)?$value->device_id:'',
	            "fcm_token"=> isset($value->fcm_token)?$value->fcm_token:'',
	            "provider"=> isset($value->provider)?$value->provider:'',
	            "provider_id"=> isset($value->provider_id)?$value->provider_id:'',
	            "facebook_url"=> isset($value->facebook_url)?$value->facebook_url:'',
	            "instagram_url"=> isset($value->instagram_url)?$value->instagram_url:'',
	            "created_at"=> isset($value->created_at)?$value->created_at:'',
	            "updated_at"=> isset($value->updated_at)?$value->updated_at:'',
	            "stores"=>$store_data,
	            //"storetype"=>isset($value->storetype)?$value->storetype:'',
	            "sellerslider"=>$sellerslider,

	            ];
	        }
       
    
       return $this->sendResponse($data,'fetch dashboard data');
    }

    public function createstore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'         => 'required|integer',
            'business_name'     => 'required|unique:stores',
            'name'              => 'required',
            'store_type'        => 'required'
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        //check store already exists or not
        $store_data = Stores::where('seller_id', $request->seller_id)->first();
        if(empty($store_data))
        {
            
            $obj = new Stores;
            $obj->seller_id       = isset($request->seller_id) ? $request->seller_id : '';
            $obj->business_name   = isset($request->business_name) ? $request->business_name : '';
            $obj->name   = isset($request->name) ? $request->name : '';
            $obj->store_type      = isset($request->store_type) ? $request->store_type : '';
            $obj->save();

            $refferal_by ='';
            if(!empty($request->refferal_by))
            {
                   $refferal_by =   $request->refferal_by; 
            }
            $business_name_app = str_replace(' ', '', $request->business_name);
            Sellers::where('id',$request->seller_id)->update(['app_id'=>$business_name_app]);

            return $this->sendResponse($obj,'Store created successfully.');
        }
        else
        {
            return $this->sendError('Store already exists.',[]);
        }
    }

    public function  updatestore(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $store_data = Stores::where('seller_id',$request->seller_id)->first();

        if(empty($store_data))
        {
            return $this->sendError('Store does not exist.please create store.', []);
        }
        if($request->hasfile('logo'))
        {
            $img = $request->file('logo');
            $imagename = rand().'.'.$img->getClientOriginalExtension();
            $img->move(STORE_LOGO_ROOT_PATH,$imagename);
        }

        $array_req_data = [
            'name' => isset($request->name)?$request->name:'',
            'logo' => isset($imagename)?$imagename:'',
            'address' => isset($request->address)?$request->address:'',
            'city' => isset($request->city)?$request->city:'',
            'state' => isset($request->state)?$request->state:'',
            'country' => isset($request->country)?$request->country:'',
            'pin_code' => isset($request->pin_code)?$request->pin_code:'',
            'theme_color' => isset($request->theme_color)?$request->theme_color:'',
            'tag_line' => isset($request->tag_line)?$request->tag_line:'',
            'slideshow' => isset($request->slideshow)?$request->slideshow:'',
            'explore_more' => isset($request->explore_more)?$request->explore_more:'',
            'new_arrival' => isset($request->new_arrival)?$request->new_arrival:'',
            'single_product' => isset($request->single_product)?$request->single_product:'',
            'recommended_for_you' => isset($request->recommended_for_you)?$request->recommended_for_you:'',
            'customer_review' => isset($request->customer_review)?$request->customer_review:'',
            'image_action' => isset($request->image_action)?$request->image_action:'',
            'privacy_policy' => isset($request->privacy_policy)?$request->privacy_policy:'',
            'return_refund_policy' => isset($request->return_refund_policy)?$request->return_refund_policy:'',
            'shipping_policy' => isset($request->shipping_policy)?$request->shipping_policy:'',
            'terms_conditions' => isset($request->terms_conditions)?$request->terms_conditions:'',
            'payments_policy' => isset($request->payments_policy)?$request->payments_policy:'',
            'about_us' => isset($request->about_us)?$request->about_us:'',
            ];

        $array_req_data = array_filter($array_req_data);

        $data = Stores::where('seller_id',$request->seller_id)->update($array_req_data);

        return $this->sendResponse('','Store update Successfully.');
    }

    public function  updatesellerprofile(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }


        if($request->hasfile('aadhar_card'))
        {
            $aadhar_card = $request->file('aadhar_card');
            $aadhar_card_doc = rand().'.'.$aadhar_card->getClientOriginalExtension();
            $aadhar_card->move(DOCUMENT_ROOT_PATH,$aadhar_card_doc);
        }

        if($request->hasfile('pan_card'))
        {
            $pan_card = $request->file('pan_card');
            $pan_card_doc = rand().'.'.$pan_card->getClientOriginalExtension();
            $pan_card->move(DOCUMENT_ROOT_PATH,$pan_card_doc);
        }

        if($request->hasfile('profile_image'))
        {
            $profile_image = $request->file('profile_image');
            $profile_image_pic = rand().'.'.$profile_image->getClientOriginalExtension();
            $profile_image->move(DOCUMENT_ROOT_PATH,$profile_image_pic);
        }

        $array_req_data = [
            'theme' => isset($request->theme)?$request->theme:'',
            'name' => isset($request->name)?$request->name:'',
            'social_id' => isset($request->social_id)?$request->social_id:'',
            'address' => isset($request->address)?$request->address:'',
            'billing_address' => isset($request->billing_address)?$request->billing_address:'',
            'aadhar_card' => isset($aadhar_card_doc)?$aadhar_card_doc:'',
            'pan_card' => isset($pan_card_doc)?$pan_card_doc:'',
            'image' => isset($profile_image_pic)?$profile_image_pic:'',
            'gst' => isset($request->gst)?$request->gst:'',
            'device_id' => isset($request->device_id)?$request->device_id:'',
            'fcm_token' => isset($request->fcm_token)?$request->fcm_token:'',
            'provider' => isset($request->provider)?$request->provider:'',
            'provider_id' => isset($request->provider_id)?$request->provider_id:'',
            'facebook_url' => isset($request->facebook_url)?$request->facebook_url:'',
            'instagram_url' => isset($request->instagram_url)?$request->instagram_url:''];

        $array_req_data = array_filter($array_req_data);

        $data = Sellers::where('id',$request->seller_id)->update($array_req_data);

        return $this->sendResponse('','Seller profile update Successfully.');
    }

    public function deletestore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required',
            'store_id' => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $data = Stores::where('id',$request->store_id)->delete();

        return $this->sendResponse($data,'Store deleted Successfully.');

    }

    public function sellerstore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required',
           
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $data = Stores::where('seller_id',$request->seller_id)->orderBy('id', 'DESC')->get();

        return $this->sendResponse($data,'Store list Successfully.');

    }

    /**
     * Seller Store Api Section
     *
     * @End Seller Store Api Section
    */

    /**
     * Catelogue Api Section
     *
     *  @Start Seller Catelogue Api Section
    */
    public function sellercataloguelist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer',
            
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.',[]);
        }

        // check catalogue details
        $data = Catalogue::where("seller_id", $request->seller_id)->get();
        
       if(empty($data)){
            return $this->sendError('Catalogues does not exist.', []);
        }
       
        $data = Catalogue::where('seller_id',$request->seller_id)->orderBy('id', 'DESC')->get();
        
        $newdata = array();
        foreach ($data as $key=>$catalogue) 
        {
            array_push($newdata,[
                'id'=>isset($catalogue->id)?$catalogue->id:'',
                "seller_id"=> isset($catalogue->seller_id)?$catalogue->seller_id:'',
                "category_id"=> isset($catalogue->category_id)?$catalogue->category_id:'',
                "name"=> isset($catalogue->name)?$catalogue->name:'',
                "image"=> isset($catalogue->image)?CATELOGUE_URL.$catalogue->image:'',
                "share_url"=>url('/').'/'.$user_detail->app_id.'/catalogs/'.$catalogue->id,
                "status"=> isset($catalogue->status)?$catalogue->status:'',
                "created_at"=> isset($catalogue->created_at)?$catalogue->created_at:'',
                "updated_at"=> isset($catalogue->updated_at)?$catalogue->updated_at:'',
                "deleted_at"=> isset($catalogue->deleted_at)?$catalogue->deleted_at:''
            ]);
        }      
         
        return $this->sendResponse($newdata,'Catalogue list.');

    }
    public function addsellercatalogue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer',
            'category_id' => 'required|integer',
            'name' => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        if($request->hasfile('image'))
        {
            $image = $request->file('image');
            $img_name =time().rand(99,100).'.'.$image->getClientOriginalExtension();
            $image->move(CATELOGUE_ROOT_PATH, $img_name);  
            
        }

        $obj =  new Catalogue;
        $obj->seller_id = $request->seller_id;
        $obj->category_id = $request->category_id;
        $obj->image = $img_name;
        $obj->name = $request->name;
        $obj->status = ACTIVE;
        $obj->save();

        return $this->sendResponse($obj,'Catalogue added Successfully.');

    }

    public function updatesellercatalogue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer',
            'catalogue_id' => 'required|integer',
            'category_id' => 'required|integer',
            'name' => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.',[]);
        }

        // check catalogue details
        $catelogue_detail = Catalogue::find($request->catalogue_id);
       
        if(empty($catelogue_detail)){
            return $this->sendError('Catalogue does not exist.', []);
        }

        if($request->hasfile('image'))
        {
            $image = $request->file('image');
            $img_name =time().rand(99,100).'.'.$image->getClientOriginalExtension();
            $image->move(CATELOGUE_ROOT_PATH, $img_name);  
            
        }

        $array_req_data = [
            'name' => isset($request->name)?$request->name:'',
            'category_id' => isset($request->category_id)?$request->category_id:'',
            'image' => isset($img_name)?$img_name:'',
            ];

        $array_req_data = array_filter($array_req_data);

        $data = Catalogue::where('id',$request->catalogue_id)->update($array_req_data);

        $Cataloguedata = Catalogue::where('id',$request->catalogue_id)->first();
        $Cataloguedata->image = isset($Cataloguedata->image)?CATELOGUE_URL.$Cataloguedata->image:'';
        $Cataloguedata->deleted_at = isset($Cataloguedata->deleted_at)?$Cataloguedata->deleted_at:'';


        return $this->sendResponse($Cataloguedata,'Catalogue updated Successfully.');

    }

    /*Delete Seller Catelogue Api*/
    public function deletesellercatalogue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required',
            'catalogue_id' => 'required',
        ]);
   
        if($validator->fails()){
            //return $this->sendError('Validation Error.', $validator->errors());
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);

        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('Unauthorised.', ['error'=>'User does not exist']);
        }

        $catelogue_data = Catalogue::where('seller_id',$request->seller_id)->where('id',$request->catalogue_id)->first();

        if(empty($catelogue_data))
        {
            return $this->sendError('Catelogue id and Seller id does not match.', []);
        }
        else
        {
            $data = Catalogue::where('seller_id',$request->seller_id)->where('id',$request->catalogue_id)->delete();
            return $this->sendResponse($data,'Catalogue deleted Successfully.');
        }
    }
    /*Delete Seller Catelogue Api*/

    /**
     * Catelogue Api Section
     *
     * @End Seller Catelogue Api Section
    */

    /**
     * Single Catelogue Product Api Section
     *
     * @Start Single Catelogue Product Api Section
    */


    public function singlecatalogueproductlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required',
            'catalogue_id' => 'required',
            
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $Products = Products::query();

        if($request->minimum_price!='' || $request->maximum_price!='')
        {
            $Products->whereBetween('price',[(int)$request->minimum_price,(int)$request->maximum_price]);
        }

        if($request->name)
        {
            $Products->where('name','like','%'.$request->name.'%');
        }

        if($request->quntity)
        {
            $Products->where('product_stock_qty','like','%'.$request->quntity.'%');
        }

        $data = $Products->with('product_image','productsvariations')->where('catalogue_id',$request->catalogue_id)->where('seller_id',$request->seller_id)->orderBy('id', 'DESC')->get();


        $newdata = array();
        foreach ($data as $key => $catalogue) {

            $product_image = $catalogue->product_image;
            $productsvariations = isset($catalogue->productsvariations)?$catalogue->productsvariations:'';

            $prod_img =[];
            foreach($product_image as $val)
            {
                $prod_img[] = ['id'=>$val['id'],'image'=>PRODUCT_URL.$val['image']];
            }
            
            $prod_variation =[];
            if(!empty($productsvariations))
            {
                foreach($productsvariations as $pro_var_val)
                {
                    
                    $prod_variation[] = ['id'=>isset($pro_var_val['id'])?$pro_var_val['id']:'','image'=>isset($pro_var_val['image'])?PRODUCT_URL.$pro_var_val['image']:'',"share_url"=>url('/').'/'.$user_detail->app_id.'/single-product-detail/'.$pro_var_val['id'],'product_id'=>isset($pro_var_val['product_id'])?$pro_var_val['product_id']:'','sku'=>isset($pro_var_val['sku'])?$pro_var_val['sku']:'','price'=>isset($pro_var_val['price'])?$pro_var_val['price']:'','discount_price'=>isset($pro_var_val['discount_price'])?$pro_var_val['discount_price']:'','product_stock_qty'=>isset($pro_var_val['product_stock_qty'])?$pro_var_val['product_stock_qty']:'','total_stock'=>isset($pro_var_val['total_stock'])?$pro_var_val['total_stock']:'','attr_value1'=>isset($pro_var_val['attr_value1'])?$pro_var_val['attr_value1']:'','attr_value2'=>isset($pro_var_val['attr_value2'])?$pro_var_val['attr_value2']:'','attr_value3'=>isset($pro_var_val['attr_value3'])?$pro_var_val['attr_value3']:'','attr_value4'=>isset($pro_var_val['attr_value4'])?$pro_var_val['attr_value4']:''];
                }
            }

            array_push($newdata,[
                                'id'=>isset($catalogue->id)?$catalogue->id:'',
                                "seller_id"=> isset($catalogue->seller_id)?$catalogue->seller_id:'',
                                "catalogue_id"=> isset($catalogue->catalogue_id)?$catalogue->catalogue_id:'',
                                "name"=> isset($catalogue->name)?$catalogue->name:'',
                                "sku"=> isset($catalogue->sku)?$catalogue->sku:'',
                                "image"=> isset($prod_img)?$prod_img:'',
                                "share_url"=>url('/').'/'.$user_detail->app_id.'/single-product-detail/'.$catalogue->id,
                                "description"=> isset($catalogue->description)?$catalogue->description:'',
                                "price"=> isset($catalogue->price)?$catalogue->price:'',
                                "product_stock_qty"=> isset($catalogue->product_stock_qty)?$catalogue->product_stock_qty:'',
                                'total_stock'=>isset($catalogue->total_stock)?$catalogue->total_stock:'',
                                "discount_price"=> isset($catalogue->discount_price)?$catalogue->discount_price:'',
                                "status"=> isset($catalogue->status)?$catalogue->status:'',
                                "created_at"=> isset($catalogue->created_at)?$catalogue->created_at:'',
                                "updated_at"=> isset($catalogue->updated_at)?$catalogue->updated_at:'',
                                "deleted_at"=> isset($catalogue->deleted_at)?$catalogue->deleted_at:'',
                                "prod_variation"=>isset($prod_variation)?$prod_variation:''
                            ]);
        }
        
        return $this->sendResponse($newdata,'Catalogue list.');

    }

    public function addcatalogueproduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'    => 'required|integer',
            'catalogue_id' => 'required|integer',
            'type'         => 'required',
            'name'         => 'required',
            'price'        => 'required',
            'sku'          => 'required',
            'discount_price' => 'required',
            'description'  => 'required',
            'product_stock_qty' => 'required',

        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        if($request->hasfile('image'))
        {
            $image = $request->file('image');
            $img_arr = [];
            $i = 1;
            foreach ($image as $value) {

                $img_name =time().rand(99,100).$i++.'.'.$value->getClientOriginalExtension();
                $value->move(PRODUCT_ROOT_PATH, $img_name); 
                $img_arr[] = $img_name;
            }
        }
        

        $obj                =   new Products;
        $obj->seller_id     =   $request->seller_id;
        $obj->catalogue_id  =   $request->catalogue_id;
        $obj->name          =   $request->name;
        $obj->image         =   isset($img_arr[0])?$img_arr[0]:'';
        $obj->sku           =   isset($request->sku)?$request->sku:'';
        $obj->type          =   isset($request->type)?$request->type:'';
        $obj->description   =   isset($request->description)?$request->description:'';
        $obj->price         =   $request->price;
        $obj->product_stock_qty = $request->product_stock_qty;
        $obj->discount_price    =   $request->discount_price;
        $obj->status        =   ACTIVE;
        $obj->save();

        $product_insert_id  =  $obj->id;
        if(!empty($img_arr))
        {
            foreach ($img_arr as $value) {

                $product_img             =   new Productsimage;
                $product_img->product_id =   $product_insert_id;
                $product_img->image      =   $value;
                $product_img->save();

            }
        }

        
        ///save variation product
        if(!empty($request->variation_product) && $request->type=="variation")
        {

            $k=0;
            foreach ($request->variation_product as $key => $vari_pro_value) {
                
                if(!empty($vari_pro_value['image']))
                {
                        $img = $vari_pro_value['image'];
                        $imagename = rand().$k++.'.'.$img->getClientOriginalExtension();
                        $img->move(PRODUCT_ROOT_PATH,$imagename);
                }

                $CatalougeVariations = CatalougeVariations::findOrFail($vari_pro_value['catalogue_variation_id']);

                $obj                 =    new Productsvariations;
                $obj->catalogue_id   =   $request->catalogue_id;
                $obj->product_id     =    $product_insert_id;
                $obj->sku            =    $vari_pro_value['sku'];
                $obj->price          =    $vari_pro_value['price'];
                $obj->discount_price    =    $vari_pro_value['discount_price'];
                $obj->product_stock_qty =    $vari_pro_value['product_stock_qty'];
                $obj->image          =    $imagename;
                $obj->attr_value1    =    isset($CatalougeVariations->attr_value1)?$CatalougeVariations->attr_value1:'';
                $obj->attr_value2    =    isset($CatalougeVariations->attr_value2)?$CatalougeVariations->attr_value2:'';
                $obj->attr_value3    =    isset($CatalougeVariations->attr_value3)?$CatalougeVariations->attr_value3:'';
                $obj->attr_value4    =    isset($CatalougeVariations->attr_value4)?$CatalougeVariations->attr_value4:'';
                $obj->save();               
            }

        }

         ///end save variation product
        $data = Products::with('product_image','productsvariations')->where('id',$product_insert_id)->orderBy('id', 'DESC')->get();
        

        $newdata = array();
        foreach ($data as $key => $catalogue) {

            $product_image = $catalogue->product_image;
            
            $productsvariations = $catalogue->productsvariations;

            $prod_img =[];
            foreach($product_image as $val)
            {
                $prod_img[] = ['id'=>$val['id'],'image'=>PRODUCT_URL.$val['image']];
            }

            $prod_variation =[];
            if(!empty($productsvariations))
            {
                foreach($productsvariations as $pro_var_val)
                {
                    
                    $prod_variation[] = ['id'=>$pro_var_val['id'],'image'=>PRODUCT_URL.$pro_var_val['image'],'product_id'=>$pro_var_val['product_id'],'sku'=>$pro_var_val['sku'],'price'=>$pro_var_val['price'],'discount_price'=>$pro_var_val['discount_price'],'product_stock_qty'=>$pro_var_val['product_stock_qty'],'attr_value1'=>isset($pro_var_val['attr_value1'])?$pro_var_val['attr_value1']:'','attr_value2'=>isset($pro_var_val['attr_value2'])?$pro_var_val['attr_value2']:'','attr_value3'=>isset($pro_var_val['attr_value3'])?$pro_var_val['attr_value3']:'','attr_value4'=>isset($pro_var_val['attr_value4'])?$pro_var_val['attr_value4']:''];
                }
            }
            
           
            array_push($newdata,[
                                'id'=>isset($catalogue->id)?$catalogue->id:'',
                                "seller_id"=> isset($catalogue->seller_id)?$catalogue->seller_id:'',
                                "catalogue_id"=> isset($catalogue->catalogue_id)?$catalogue->catalogue_id:'',
                                "name"=> isset($catalogue->name)?$catalogue->name:'',
                                "sku"=> isset($catalogue->sku)?$catalogue->sku:'',
                                "image"=> isset($prod_img)?$prod_img:'',
                                "description"=> isset($catalogue->description)?$catalogue->description:'',
                                "price"=> isset($catalogue->price)?$catalogue->price:'',
                                "product_stock_qty"=> isset($catalogue->product_stock_qty)?$catalogue->product_stock_qty:'',
                                "discount_price"=> isset($catalogue->discount_price)?$catalogue->discount_price:'',
                                "status"=> isset($catalogue->status)?$catalogue->status:'',
                                "created_at"=> isset($catalogue->created_at)?$catalogue->created_at:'',
                                "updated_at"=> isset($catalogue->updated_at)?$catalogue->updated_at:'',
                                "deleted_at"=> isset($catalogue->deleted_at)?$catalogue->deleted_at:'',
                                "variation_product"=> isset($prod_variation)?$prod_variation:'',
                            ]);
        }

        return $this->sendResponse($newdata,'Catalogue product added successfully.');

    }

    public function updatecatalogueproduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required',
            'catalogue_id' => 'required',
            'product_id' => 'required',
            'type'         => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $array_req_data = [
            'name' => isset($request->name)?$request->name:'',
            'seller_id' => isset($request->seller_id)?$request->seller_id:'',
            'catalogue_id' => isset($request->catalogue_id)?$request->catalogue_id:'',
            'sku'   => isset($request->sku)?$request->sku:'',
            'type'  => isset($request->type)?$request->type:'',
            'price' => isset($request->price)?$request->price:'',
            'product_stock_qty' => isset($request->product_stock_qty)?$request->product_stock_qty:'',
            'description' => isset($request->description)?$request->description:'',
            'discount_price' => isset($request->discount_price)?$request->discount_price:'',
            'status' => isset($request->status)?$request->status:ACTIVE,
            ];

        $array_req_data = array_filter($array_req_data);

        Products::where('id',$request->product_id)->update($array_req_data);

        if($request->type=="variation" && !empty($request->variation_product))
        {
            

            foreach ($request->variation_product as $vari_pro_value) {
                if(!empty($vari_pro_value['image']))
                {
                        $img = $vari_pro_value['image'];
                        $imagename = rand().$k++.'.'.$img->getClientOriginalExtension();
                        $img->move(PRODUCT_ROOT_PATH,$imagename);
                }

                $array_req_data_var = [
                    'sku'            =>    $vari_pro_value['sku'],
                    'price'          =>    $vari_pro_value['price'],
                    'discount_price'    =>    $vari_pro_value['discount_price'],
                    'product_stock_qty' =>    $vari_pro_value['product_stock_qty'],
                    'image'          =>    isset($imagename)?$imagename:''
                    ];

                $array_req_data_var = array_filter($array_req_data_var);
                Productsvariations::where('id',$vari_pro_value['id'])->update($array_req_data_var);
            }
           

        }
        ///product data
        $data = Products::with('product_image','productsvariations')->where('id',$request->product_id)->orderBy('id', 'DESC')->get();
        

        $newdata = array();
        foreach ($data as $key => $catalogue) {

            $product_image = $catalogue->product_image;
            
            $productsvariations = $catalogue->productsvariations;

            $prod_img =[];
            foreach($product_image as $val)
            {
                $prod_img[] = ['id'=>$val['id'],'image'=>PRODUCT_URL.$val['image']];
            }

            $prod_variation =[];
            if(!empty($productsvariations))
            {
                foreach($productsvariations as $pro_var_val)
                {
                    
                    $prod_variation[] = ['id'=>$pro_var_val['id'],'image'=>PRODUCT_URL.$pro_var_val['image'],'product_id'=>$pro_var_val['product_id'],'sku'=>$pro_var_val['sku'],'price'=>$pro_var_val['price'],'discount_price'=>$pro_var_val['discount_price'],'product_stock_qty'=>$pro_var_val['product_stock_qty'],'attr_value1'=>isset($pro_var_val['attr_value1'])?$pro_var_val['attr_value1']:'','attr_value2'=>isset($pro_var_val['attr_value2'])?$pro_var_val['attr_value2']:'','attr_value3'=>isset($pro_var_val['attr_value3'])?$pro_var_val['attr_value3']:'','attr_value4'=>isset($pro_var_val['attr_value4'])?$pro_var_val['attr_value4']:''];
                }
            }
            
           
            array_push($newdata,[
                                'id'=>isset($catalogue->id)?$catalogue->id:'',
                                "seller_id"=> isset($catalogue->seller_id)?$catalogue->seller_id:'',
                                "catalogue_id"=> isset($catalogue->catalogue_id)?$catalogue->catalogue_id:'',
                                "name"=> isset($catalogue->name)?$catalogue->name:'',
                                "sku"=> isset($catalogue->sku)?$catalogue->sku:'',
                                "image"=> isset($prod_img)?$prod_img:'',
                                "description"=> isset($catalogue->description)?$catalogue->description:'',
                                "price"=> isset($catalogue->price)?$catalogue->price:'',
                                "product_stock_qty"=> isset($catalogue->product_stock_qty)?$catalogue->product_stock_qty:'',
                                "discount_price"=> isset($catalogue->discount_price)?$catalogue->discount_price:'',
                                "status"=> isset($catalogue->status)?$catalogue->status:'',
                                "created_at"=> isset($catalogue->created_at)?$catalogue->created_at:'',
                                "updated_at"=> isset($catalogue->updated_at)?$catalogue->updated_at:'',
                                "deleted_at"=> isset($catalogue->deleted_at)?$catalogue->deleted_at:'',
                                "variation_product"=> isset($prod_variation)?$prod_variation:'',
                            ]);
        }

        return $this->sendResponse($newdata,'Product updated Successfully.');

    }

    public function deletecatalogueproduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required',
            'catalogue_id' => 'required',
            'product_id' => 'required',
            
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $seller_product = Products::where('seller_id',$request->seller_id)->where('catalogue_id',$request->catalogue_id)->where('id',$request->product_id)->first();
        if(!empty($seller_product))
        {
            $data = Products::where('seller_id',$request->seller_id)->where('catalogue_id',$request->catalogue_id)->where('id',$request->product_id)->delete();
            Productsimage::where('product_id',$request->product_id)->delete();
            Productsvariations::where('product_id',$request->product_id)->delete();
            return $this->sendResponse([],'Catalogue product deleted successfully.');
        }
        else
        {
            return $this->sendError('Seller product does not exist.',[]);
        }

    }
    /**
     * Single Catelogue Product Api Section
     *
     * @End Single Catelogue Product Api Section
    */



    /**
     * Seller Staff Api Section
     *
     * @Start Seller Staff Api Section
    */   

    //add staff api
    public function addsellerstaff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|numeric',
            'phone'     => 'required|numeric|digits:10|unique:seller_staff',
            'name'     => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }
       
       $obj                = new SellerStaff;
        if($request->hasfile('image'))
            {
                $img = $request->file('image');
                $imagename = rand().'_staff.'.$img->getClientOriginalExtension();
                $img->move(STAFF_LOGO_ROOT_PATH,$imagename);
                $obj->image     = $imagename;
            }
        
        $obj->seller_id     = $request->seller_id;
        $obj->phone         = $request->phone;
        $obj->name          = $request->name;
        $obj->permission    = $request->permission;
        $obj->save();

        return $this->sendResponse($obj,'Staff added successfully.');
    }

    //update staff 
    public function updatesellerstaff(Request $request)
    {
        $id = $request->id;
        $imagename = '';
        $validator = Validator::make($request->all(), [
            'id'        => 'required|numeric',
            'seller_id' => 'required|numeric',
            'phone'     => 'required|numeric|digits:10|unique:seller_staff,phone,'.$id,
            'name'      => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

            if($request->hasfile('image'))
            {
                $img = $request->file('image');
                $imagename = rand().'_staff.'.$img->getClientOriginalExtension();
                $img->move(STAFF_LOGO_ROOT_PATH,$imagename);
            }

         $array_req_data = [
            'seller_id' => isset($request->seller_id)?$request->seller_id:'',
            'phone' => isset($request->phone)?$request->phone:'',
            'name' => isset($request->name)?$request->name:'',
            'image' => isset($imagename)?$imagename:'',
            'permission' => isset($request->permission)?$request->permission:'',
            'status' => isset($request->status)?$request->status:ACTIVE,
            ];

        $array_req_data = array_filter($array_req_data);

        //check staff id exists or not
        $staff_data = SellerStaff::where('id',$request->id)->where('seller_id',$request->seller_id)->first();
        if(!empty($staff_data))
        {
            $data = SellerStaff::where('id',$request->id)->where('seller_id',$request->seller_id)->update($array_req_data);
            return $this->sendResponse($data,'Staff Updated Successfully.');
        }
        else
        {
            return $this->sendError('Staff does not exist.', []);
        } 
    }

    //delete staff 
    public function deletesellerstaff(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'id'        => 'required|integer',
            'seller_id' => 'required|integer',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        //check staff id or seller id exists or not
        $staff_data = SellerStaff::where('id',$request->id)->where('seller_id',$request->seller_id)->first();
        if(!empty($staff_data))
        {
            $data = SellerStaff::where('id',$request->id)->where('seller_id',$request->seller_id)->delete();
            return $this->sendResponse('','Staff Deleted Successfully.');
        }
        else
        {
            return $this->sendError('Staff does not exist.', []);
        } 
    }


    //list staff 
    public function listsellerstaff(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        //check staff id or seller id exists or not
        $staff_data = SellerStaff::where('seller_id',$request->seller_id)->first();
        if(!empty($staff_data))
        {
            $data = SellerStaff::where('seller_id',$request->seller_id)->orderBy('id','DESC')->get();
            $newdata = array();
            foreach ($data as $key => $staff) {
                if(!empty($staff->image))
                {
                    $staff_img = STAFF_LOGO_URL.$staff->image;
                }
                else
                {
                    $staff_img = "";
                }
                array_push($newdata,
                    [
                        'id'          => isset($staff->id)?$staff->id:'',
                        "seller_id"   => isset($staff->seller_id)?$staff->seller_id:'',
                        "phone"       => isset($staff->phone)?$staff->phone:'',
                        "name"        => isset($staff->name)?$staff->name:'',
                        "image"       => $staff_img,
                        "permission"  => isset($staff->permission)?$staff->permission:'',
                        "status"      => isset($staff->status)?$staff->status:'',
                        "created_at"  => isset($staff->created_at)?$staff->created_at:'',
                        "updated_at"  => isset($staff->updated_at)?$staff->updated_at:'',
                        "deleted_at"  => isset($staff->deleted_at)?$staff->deleted_at:''
                    ]
                );
            }
            return $this->sendResponse($newdata,'Staff List.');
        }
        else
        {
            return $this->sendError('Staff does not exist.', []);
        } 
    }

    /**
     * Seller Staff Api Section
     *
     * @End Seller Staff Api Section
    */




    /**
     * Seller Customer Api Section
     *
     * @Start Seller Customer Api Section
    */   

    //add staff api
    public function addsellercustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|numeric',
            'phone'     => 'required|numeric|digits:10|unique:seller_customer',
            'name'     => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }
        
        $obj                    = new SellerCustomer;

        if($request->hasfile('image'))
        {
            $img = $request->file('image');
            $imagename = rand().'_customer.'.$img->getClientOriginalExtension();
            $img->move(CUSTOMER_LOGO_ROOT_PATH,$imagename);

            $obj->image         = $imagename;
        }

       
        $obj->seller_id         = $request->seller_id;
        $obj->phone             = $request->phone;
        $obj->name              = $request->name;
        $obj->referal_program   = $request->referal_program;
        $obj->save();

        return $this->sendResponse($obj,'Customer added successfully.');
    }

    //update staff 
    public function updatesellercustomer(Request $request)
    {
        $id = $request->id;
        $imagename = '';
        $validator = Validator::make($request->all(), [
            'id'        => 'required|numeric',
            'seller_id' => 'required|numeric',
            'phone'     => 'required|numeric|digits:10|unique:seller_customer,phone,'.$id,
            'name'      => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }
        
        if($request->hasfile('image'))
        {
            $img = $request->file('image');
            $imagename = rand().'_customer.'.$img->getClientOriginalExtension();
            $img->move(CUSTOMER_LOGO_ROOT_PATH,$imagename);
        }

        $array_req_data = [
            'seller_id' => isset($request->seller_id)?$request->seller_id:'',
            'phone' => isset($request->phone)?$request->phone:'',
            'name' => isset($request->name)?$request->name:'',
            'image' => isset($imagename)?$imagename:'',
            'referal_program' => isset($request->referal_program)?$request->referal_program:'',
            'status' => isset($request->status)?$request->status:ACTIVE,
        ];

        $array_req_data = array_filter($array_req_data);

        //check staff id exists or not
        $staff_data = SellerCustomer::where('id',$request->id)->where('seller_id',$request->seller_id)->first();
        if(!empty($staff_data))
        {
            $data = SellerCustomer::where('id',$request->id)->where('seller_id',$request->seller_id)->update($array_req_data);
            return $this->sendResponse($data,'Customer Updated Successfully.');
        }
        else
        {
            return $this->sendError('Customer does not exist.', []);
        } 
    }

    //delete staff 
    public function deletesellercustomer(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'id'        => 'required|integer',
            'seller_id' => 'required|integer',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        //check staff id or seller id exists or not
        $staff_data = SellerCustomer::where('id',$request->id)->where('seller_id',$request->seller_id)->first();
        if(!empty($staff_data))
        {
            $data = SellerCustomer::where('id',$request->id)->where('seller_id',$request->seller_id)->delete();
            return $this->sendResponse('','Customer Deleted Successfully.');
        }
        else
        {
            return $this->sendError('Customer does not exist.', []);
        } 
    }


    //list staff 
    public function listsellercustomer(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|integer',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        //check staff id or seller id exists or not
        $staff_data = SellerCustomer::where('seller_id',$request->seller_id)->first();
        if(!empty($staff_data))
        {
            $data = SellerCustomer::where('seller_id',$request->seller_id)->orderBy('id','DESC')->get();
            $newdata = array();
            foreach ($data as $key => $customer) {
                if(!empty($customer->image))
                {
                    $customer_img = CUSTOMER_LOGO_URL.$customer->image;
                }
                else
                {
                    $customer_img = "";
                }
                array_push($newdata,
                    [
                        'id'                => isset($customer->id)?$customer->id:'',
                        "seller_id"         => isset($customer->seller_id)?$customer->seller_id:'',
                        "phone"             => isset($customer->phone)?$customer->phone:'',
                        "name"              => isset($customer->name)?$customer->name:'',
                        "image"             => $customer_img,
                        "referal_program"   => isset($customer->referal_program)?$customer->referal_program:'',
                        "permission"        => isset($customer->permission)?$customer->permission:'',
                        "status"            => isset($customer->status)?$customer->status:'',
                        "created_at"        => isset($customer->created_at)?$customer->created_at:'',
                        "updated_at"        => isset($customer->updated_at)?$customer->updated_at:'',
                        "deleted_at"        => isset($customer->deleted_at)?$customer->deleted_at:''
                    ]
                );
            }
            return $this->sendResponse($newdata,'Customer List.');
        }
        else
        {
            return $this->sendError('Customer does not exist.', []);
        } 
    }

    /**
     * Seller Customer Api Section
     *
     * @End Seller Customer Api Section
    */



    /**
     * Plan/Packages Api Section
     *
     * @Start Plan/Packages Api Section
    */

    /*Start Plan/Packages List Api Section*/
    public function packageslist(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'seller_id' => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        // check user details
        $data = Plans::orderBy('id', 'DESC')->get();
        $data_arr = [];
        foreach ($data as $value) {

            array_push($data_arr,[
                'id'=>$value->id,
                'name'=>isset($value->name)?$value->name:'',
                'price'=>isset($value->price)?$value->price:'',
                'description'=>isset($value->description)?$value->description:'',
                'created_at'=>isset($value->created_at)?$value->created_at:'',
                'seller_coin'=>isset($user_detail->coin)?$user_detail->coin:0
            ]);
        }
        if(empty($data_arr)){
            return $this->sendError('Packages does not exist.', []);
        }

        return $this->sendResponse($data_arr,'Packages list.');
    }
    /*End Plan/Packages List Api Section*/

    /**
     * Plan/Packages Api Section
     *
     * @End Plan/Packages Api Section
    */

    /**
     * All Payments Api Section
     *
     * @Start Payments Api Section
    */

    /*Start Payments List Api Section*/
    public function sellerpaymentslist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }



        // check seller order details
        $seller_payment_details = Payments::where("seller_id",$request->seller_id)->orderBy('id', 'DESC')->get();

        if(count($seller_payment_details) > 0 && !empty($seller_payment_details))
        {
            return $this->sendResponse($seller_payment_details,'Seller Payments List.');
        }
        else
        {
            return $this->sendError('Empty payment list.', []);
        }
    
    }
    /*End Payments List Api Section*/

    /**
     * All Payments Api Section
     *
     * @End Payments Api Section
    */


    /**
     * Social Login Api Section
     *
     * @Start Social Login Api Section
    */

    //social login start
    public function sociallogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'provider_id' => 'required',
            'provider' => 'required',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $sellers       =   Sellers::where(['email' => $request->email])->first();
        if($sellers){
            
             return $this->sendError('Seller does not exist.', []);
                    
            }else{

                    $Sellers = Sellers::create([
                            'email'         => $request->email,
                            'provider_id'   => $request->provider_id,
                            'provider'      => $request->provider,
                        ]);

                return $this->sendResponse('message','Seller created successfully.');
                     
            }
    }
    //social login end 
    /**
     * Social Login Api Section
     *
     * @End Social Login Api Section
    */

    /* Reviews Api Start*/
    public function listsellerreviews(Request $request)
    {
       $validator = Validator::make($request->all(), [
            'seller_id' => 'required|numeric',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }



        // check seller order details
        $seller_review_details = Reviews::where("seller_id",$request->seller_id)->orderBy('created_at', 'DESC')->get();

        if(count($seller_review_details) > 0 && !empty($seller_review_details))
        {
            return $this->sendResponse($seller_review_details,'Seller Reviews List.');
        }
        else
        {
            return $this->sendError('Empty Reviews list.', []);
        }
    }

    public function deletesellerreviews(Request $request)
    {
       $validator = Validator::make($request->all(), [
            'seller_id' => 'required|numeric',
            'review_id' => 'required|numeric',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }



        $review_data = Reviews::where('seller_id',$request->seller_id)->where('id',$request->review_id)->first();

        if(empty($review_data))
        {
            return $this->sendError('Review id and Seller id does not match.', []);
        }
        else
        {
            $data = Reviews::where('seller_id',$request->seller_id)->where('id',$request->review_id)->delete();
            return $this->sendResponse($data,'Review deleted Successfully.');
        }
    }

    /* Reviews Api End*/

    /* Offers Api Start*/
    public function listselleroffer(Request $request)
    {
       $validator = Validator::make($request->all(), [
            'seller_id' => 'required|numeric',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }



        // check seller order details
        $seller_offer_details = Offers::where("seller_id",$request->seller_id)->orderBy('created_at', 'DESC')->get();

        if(count($seller_offer_details) > 0 && !empty($seller_offer_details))
        {
            return $this->sendResponse($seller_offer_details,'Seller Offers List.');
        }
        else
        {
            return $this->sendError('Empty Offers list.', []);
        }
    }

    public function addselleroffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric',
            'offer_type'         => 'required',
            'amount_off'         => 'required',
            'minimum_purchase'   => 'required',
            'valid_date'         => 'required',
            'code'               => 'required',
            'apply_once'         => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }
       
        $obj                    = new Offers;
        $obj->seller_id         = $request->seller_id;
        $obj->offer_type        = $request->offer_type;
        $obj->amount_off        = $request->amount_off;
        $obj->minimum_purchase  = $request->minimum_purchase;
        $obj->valid_date        = $request->valid_date;
        $obj->code              = $request->code;
        $obj->apply_once        = isset($request->apply_once)?$request->apply_once:'';
        $obj->save();

        return $this->sendResponse($obj,'Offers added successfully.');
    }

    public function updateselleroffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric',
            'offer_id'          => 'required|numeric',
            'offer_type'         => 'required',
            'amount_off'         => 'required',
            'minimum_purchase'   => 'required',
            'valid_date'         => 'required',
            'code'               => 'required',
            'apply_once'         => 'required',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }
        
        $array_req_data = [
                'seller_id'         => isset($request->seller_id) ? $request->seller_id : '',
                'offer_type'             => isset($request->offer_type) ? $request->offer_type : '',
                'amount_off'             => isset($request->amount_off) ? $request->amount_off : '',
                'minimum_purchase'               => isset($request->minimum_purchase) ? $request->minimum_purchase : '',
                'valid_date'           => isset($request->valid_date) ? $request->valid_date : '',
                'code'   => isset($request->code) ? $request->code : '',
                'apply_once'   => isset($request->apply_once) ? $request->apply_once : '',
            ];
        
        $array_req_data = array_filter($array_req_data);
        $data = Offers::where('id',$request->offer_id)->update($array_req_data);

        return $this->sendResponse($data,'Offers Updated successfully.');
    }

    public function deleteselleroffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric',
            'offer_id'          => 'required|numeric',
        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $offer_data = Offers::where('seller_id',$request->seller_id)->where('id',$request->offer_id)->first();

        if(empty($offer_data))
        {
            return $this->sendError('Offer id and Seller id does not match.', []);
        }
        else
        {
            $data = Offers::where('seller_id',$request->seller_id)->where('id',$request->offer_id)->delete();
            return $this->sendResponse($data,'Offer deleted Successfully.');
        }

    }

    /* Offers Api End*/

    public function sellerreport(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'seller_id'          => 'required|numeric',

        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $totalorder = Orders::where('seller_id',$request->seller_id)->get()->count();
        $totalsales = Orders::where('seller_id',$request->seller_id)->where('status','Delivered')->get()->count();

        return $this->sendResponse(['total_order'=>$totalorder,'totalsales'=>$totalsales],'Seller Report.');

    }

    public function orderreportlist(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'seller_id'          => 'required|numeric',

        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        
        $condition = '';
        if(!empty($request->sort_by))
        {
            if($request->sort_by=='today')
            {

                $condition = date('Y-m-d');
            }
            if($request->sort_by=='weekly')
            {

                $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -8 day'));
            }
            if($request->sort_by=='last200')
            {
               $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -200 day'));
            }
            if($request->sort_by=='last1000')
            {
               $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -1000 day'));
            }

            $totalorder = Orders::where('seller_id',$request->seller_id)->where('created_at','>=',$condition)->orderBy('id','desc')->get();

            
        }else
        {
            $totalorder = Orders::where('seller_id',$request->seller_id)->orderBy('id','desc')->get();
            
        }
        if(count($totalorder)>0 && !empty($totalorder))
        {

            $data = [];
            foreach ($totalorder as $key => $value_bss) {
               
                $cartdata = [];
                if(!empty($value_bss->cart_value))
                {
                   foreach (unserialize($value_bss->cart_value) as $key => $value_cart) {

                        array_push($cartdata,['id'=>isset($value_cart['product']['id'])?$value_cart['product']['id']:'','name'=>isset($value_cart['product']['name'])?$value_cart['product']['name']:'','quantity'=>isset($value_cart['quantity'])?$value_cart['quantity']:'','price'=>isset($value_cart['product']['price'])?$value_cart['product']['price']:'','image'=>isset($value_cart['product']['image'])?PRODUCT_URL.$value_cart['product']['image']:'']);
                    } 
                }
                
               $data_rr = ["id"=> isset($value_bss['id'])?$value_bss['id']:'',
                "seller_id"=> isset($value_bss['seller_id'])?$value_bss['seller_id']:'',
                "user_id"=> isset($value_bss['user_id'])?$value_bss['user_id']:'',
                "firstname"=> isset($value_bss['firstname'])?$value_bss['firstname']:'',
                "lastname"=> isset($value_bss['lastname'])?$value_bss['lastname']:'',
                "company_name"=> isset($value_bss['company_name'])?$value_bss['company_name']:'',
                "country"=> isset($value_bss['country'])?$value_bss['country']:'',
                "address_1"=> isset($value_bss['address_1'])?$value_bss['address_1']:'',
                "address_2"=> isset($value_bss['address_2'])?$value_bss['address_2']:'',
                "city"=>isset($value_bss['city'])?$value_bss['city']:'',
                "state"=> isset($value_bss['state'])?$value_bss['state']:'',
                "postal_code"=>isset($value_bss['postal_code'])?$value_bss['postal_code']:'',
                "phone"=> isset($value_bss['phone'])?$value_bss['phone']:'',
                "email"=> isset($value_bss['email'])?$value_bss['email']:'',
                "order_comments"=>isset($value_bss['order_comments'])?$value_bss['order_comments']:'',
                "cart_value_bss"=> isset($cartdata)?$cartdata:'',
                "coupon_value_bss"=> isset($value_bss['coupon_value_bss'])?$value_bss['coupon_value_bss']:'',
                "status"=> isset($value_bss['status'])?$value_bss['status']:'',
                "price"=> isset($value_bss['price'])?$value_bss['price']:'',
                "seller_pay_status"=> isset($value_bss['seller_pay_status'])?$value_bss['seller_pay_status']:'',
                "seller_refund_status"=> isset($value_bss['seller_refund_status'])?$value_bss['seller_refund_status']:'',
                "created_at"=> isset($value_bss['created_at'])?$value_bss['created_at']:'',
                "updated_at"=> isset($value_bss['updated_at'])?$value_bss['updated_at']:''];
               
                array_push($data, $data_rr);
            }
            return $this->sendResponse($data,'Order Report List.');
        }else
        {
            return $this->sendResponse('Empty Order Report list.',[]);
        }

    }

    public function revenuelist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric'

        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }
        
        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $condition = '';
        if(!empty($request->filter))
        {
            if($request->filter=='weekly')
            {

                $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -8 day'));
            }
            if($request->filter=='last200')
            {
               $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -200 day'));
            }
            if($request->filter=='last1000')
            {
               $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -1000 day'));
            }
            
        }

        $ordertbl = Orders::query();

        if(!empty($condition))
        {

            $revenuelist = $ordertbl->where('seller_id',$request->seller_id)->where('created_at','>=',$condition)->get();
          
        }else
        {
            $revenuelist = $ordertbl->where('seller_id',$request->seller_id)->get();
        }
        
        $total_amount= 0;
        $total_return_amount= 0;
        $total_gain_amount= 0;
        
        foreach ($revenuelist as $key => $value) {
          
            if($value->status=='Return' || $value->status=='Cancelled')
            {
                
                $total_return_amount +=  $value->price;

            }else{
                $total_gain_amount +=  $value->price;
            }

            $total_amount +=  $value->price;

        }

        if(count($revenuelist)>0 && !empty($revenuelist))
        {
            return $this->sendResponse(['total_amount'=>$total_amount,'total_gain_amount'=>$total_gain_amount,'total_return_amount'=>$total_return_amount],'Revenue List.');

        }else
        {
            return $this->sendResponse('Empty Revenue list.',[]);
        }

    }
    public function sellerstoretype(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric'

        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $totalstoretype = StoreType::get();
        if(count($totalstoretype)>1 && !empty($totalstoretype))
        {
            $data =[];
            foreach ($totalstoretype as $value) {

                if(!empty($value->image))
                {
                    $storetypeimg = STORETYPE_LOGO_URL.$value->image;
                }
                else
                {
                    $storetypeimg = "";
                }

                array_push($data,[
                                    'id'=>isset($value->id)?$value->id:'',
                                    'name'=>isset($value->name)?$value->name:'',
                                    'image'=>$storetypeimg,
                                    'status'=>$value->status,
                                    'created_at'=>isset($value->created_at)?$value->created_at:'',
                                    'updated_at'=>isset($value->updated_at)?$value->updated_at:'',
                                    'deleted_at'=>isset($value->deleted_at)?$value->deleted_at:'',

                                ]);
            }
            return $this->sendResponse($data,'Store Type List.');
        }else
        {
            return $this->sendResponse('Empty Store Type list.',[]);
        }
    }

    public function updatesellerstoretype(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric',
            'store_type'          => 'required|numeric'

        ]);
   
        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        Stores::where('seller_id',$request->seller_id)->update(['store_type' =>$request->store_type]);
        return $this->sendResponse([],'Store type updated successfully.');
    }

    public function catalogueattributes(Request $request)
    {
        $all = $request->all();

        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric',
            'catalogue_id'       => 'required|numeric',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }
           
        
        $getAttributes = CatalogueAttributes::where('seller_id',$request->seller_id)->where('catalogue_id',$request->catalogue_id)->get();
        $count = $getAttributes->count();   
        if(!empty($getAttributes))
        {
            if(!array_key_exists("attributes",$all))
            {
                return $this->sendError('Does not create product attributes because of key name is (attributes).', []);
            }
            if($count >= 4)
            {
                return $this->sendError('Does not create product attributes greater than 5.','');
            }
            else
            {
                $attributes = [];
                $data = $all['attributes'];
                if(!empty($data))
                {
                    $counter = 0;
                    for ($i=0; $i < count($data); $i++) { 
                        
                        $newGetAttributes = CatalogueAttributes::where('seller_id',$request->seller_id)->where('catalogue_id',$request->catalogue_id)->get();
                        $new_count = $newGetAttributes->count();

                        if($new_count < 4)
                        {
                            $attributes[] = CatalogueAttributes::create([
                                'seller_id'         => $request->seller_id,
                                'catalogue_id'      => $request->catalogue_id,
                                'attr_name'         => $data[$i]['attr_name'],
                                'attr_value'        => $data[$i]['attr_value']
                            ]);
                            $counter++;
                        }
                        
                        
                    }
                }
                return $this->sendResponse($attributes,$counter.' attributes created successfully.');
            }
        }
        
    }

    public function getcatalogueattributes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric',
            'catalogue_id'       => 'required|numeric',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $attributes = CatalogueAttributes::where('seller_id',$request->seller_id)->where('catalogue_id',$request->catalogue_id)->get();
        $data =array();

        if(!empty($attributes))
        {   
            for ($i=0; $i < count($attributes); $i++) { 
                array_push($data,[
                                    'id'=>isset($attributes[$i]['id'])?$attributes[$i]['id']:'',
                                    'attr_name'=>isset($attributes[$i]['attr_name'])?$attributes[$i]['attr_name']:'',
                                    'attr_value'=>isset($attributes[$i]['attr_value'])?$attributes[$i]['attr_value']:'',
                                    'created_at'=>isset($attributes[$i]['created_at'])?$attributes[$i]['created_at']:'',
                                    'updated_at'=>isset($attributes[$i]['updated_at'])?$attributes[$i]['updated_at']:'',
                                    'deleted_at'=>isset($attributes[$i]['deleted_at'])?$attributes[$i]['deleted_at']:'',

                ]);
            
            }
            return $this->sendResponse($data,'Product Attribute List.');
        }
        else
        {
            return $this->sendResponse([],'Empty Product Attribute List.');
        }
    }


    public function updatecatalogueattributes(Request $request)
    {
        $all = $request->all();
        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric',
            'attribute_id'       => 'required|numeric'
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }
        //$data = $all['attributes'];

        $editAttributes = CatalogueAttributes::find($all['attribute_id']);

        if(!empty($editAttributes))
        {
            $arr_data = [
                'attr_name'   => isset($all['attr_name']) ? $all['attr_name'] : '',
                'attr_value'  => isset($all['attr_value']) ? $all['attr_value'] : ''
            ];
        
            $arr_data = array_filter($arr_data);
            $data = CatalogueAttributes::where('id',$all['attribute_id'])->update($arr_data);

            return $this->sendResponse($data,'Product Attributes Updated successfully.');
        }
        else
        {
            return $this->sendError('This attributes id data does not exists our records.', []);
        }
    }

    public function deletecatalogueattributes(Request $request)
    {
        $all = $request->all();
        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric',
            'attribute_id'       => 'required|numeric'
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $deleteAttributes = CatalogueAttributes::find($all['attribute_id']);
        if(!empty($deleteAttributes))
        {
            $data = CatalogueAttributes::where('id',$all['attribute_id'])->delete();
            return $this->sendResponse($data,'Product Attributes Deleted successfully.');
        }
        else
        {
            return $this->sendError('This attributes id data does not exists our records.', []);
        }
    }

    public function createcataloguevariation(Request $request)
    {
        $all = $request->all();
        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric',
            'attribute_id'       => 'required'
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $attribute_arr = explode(',',$all['attribute_id']);
        $CatalogueAttributes = CatalogueAttributes::select('catalogue_id','attr_name','attr_value')->whereIn('id',$attribute_arr)->get()->toArray();
        
        $att_val_arr = [];
        foreach ($CatalogueAttributes as $value) {
           
            array_push($att_val_arr,explode(',',$value['attr_value']));
        }
      
       $combinationdata =  $this->combinations($att_val_arr);

       foreach ($combinationdata as $combinationdata_value) {

           $obj = new CatalougeVariations;
           $obj->seller_id = $request->seller_id;
           $obj->catalouge_id = $CatalogueAttributes[0]['catalogue_id'];
           $obj->attr_value1 = isset($combinationdata_value[0])?$combinationdata_value[0]:'';
           $obj->attr_value2 = isset($combinationdata_value[1])?$combinationdata_value[1]:'';
           $obj->attr_value3 = isset($combinationdata_value[2])?$combinationdata_value[2]:'';
           $obj->attr_value4 = isset($combinationdata_value[3])?$combinationdata_value[3]:'';
           $obj->save();
       }
       
       return $this->sendResponse([],'Catalogue Variation Created successfully.');

    }

    public function listcataloguevariation(Request $request)
    {
        $all = $request->all();
        $validator = Validator::make($request->all(), [
            'seller_id'          => 'required|numeric',
            'catalogue_id'       => 'required'
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $CatalougeVariations = CatalougeVariations::where('seller_id',$request->seller_id)->where('catalouge_id',$request->catalogue_id)->get()->toArray();

        
        $data = [];
        foreach ($CatalougeVariations as $value) {
           $data_arr =[
            "id"            =>  $value['id'],
            "seller_id"     =>  $value['seller_id'],
            "catalouge_id"  =>  $value['catalouge_id'],
            "attr_value1"   =>  isset($value['attr_value1'])?$value['attr_value1']:'',
            "attr_value2"   =>  isset($value['attr_value2'])?$value['attr_value2']:'',
            "attr_value3"   =>  isset($value['attr_value3'])?$value['attr_value3']:'',
            "attr_value4"   =>  isset($value['attr_value4'])?$value['attr_value4']:'',
            "created_at"    =>  isset($value['created_at'])?$value['created_at']:'',
            "updated_at"    =>  isset($value['updated_at'])?$value['updated_at']:'',
            
            ]; 
           array_push($data,$data_arr);

        }
        
       return $this->sendResponse($data,'Catalogue Variation list.');

    }


    public function updatevariationproduct(Request $request)
    {
        
        $all = $request->all();
        $validator = Validator::make($request->all(), [
            'seller_id'              => 'required|numeric',
            'variation_product_id'   => 'required|numeric',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }


        if($request->hasfile('image'))
        {
            $img = $request->file('image');
            $imagename = rand().'.'.$img->getClientOriginalExtension();
            $img->move(PRODUCT_ROOT_PATH,$imagename);
            
        }

        $array_req_data = [
            'sku'               => isset($request->sku) ? $request->sku : '',
            'price'             => isset($request->price) ? $request->price : '',
            'discount_price'    => isset($request->discount_price) ? $request->discount_price : '',
            'product_stock_qty' => isset($request->product_stock_qty) ? $request->product_stock_qty : '',
            'image'             => isset($imagename) ? $imagename : '',
            'billing_address'   => isset($request->billing_address) ? $request->billing_address : '',
        ];

        $array_req_data = array_filter($array_req_data);
         Productsvariations::where('id',$request->variation_product_id)->update($array_req_data);
        $data = Productsvariations::findOrFail($request->variation_product_id);
        $data->image = isset($data->image)?PRODUCT_URL.$data->image:'';
        $data->deleted_at = isset($data->deleted_at)?$data->deleted_at:'';
        return $this->sendResponse($data,'updated  Variation Product Successfully.');

    }

    public function deleteimage(Request $request)
    {
        $all = $request->all();
        $validator = Validator::make($request->all(), [
            'seller_id'              => 'required|numeric',
            'image_id'   => 'required|numeric',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        Productsimage::where('id',$request->image_id)->delete();
        return $this->sendResponse([],'Product image deleted Successfully.');

        

    }

    public function category(Request $request)
    {

        $all = $request->all();
        $validator = Validator::make($request->all(), [
            'seller_id'             => 'required|numeric',
            'store_type_id'         => 'required|numeric'
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }
        $Category =Category::where('store_type_id',$request->store_type_id)->get();
        
        $data=[];
        foreach ($Category as $cat_value) {
           
           array_push($data,['id'=>$cat_value->id,
                            "store_type_id"=>$cat_value->store_type_id,
                            "name"=>$cat_value->name,
                            "created_at"=>$cat_value->created_at]);
        }
        return $this->sendResponse($data,'Category list.');
    }

    public function savesellerBankdetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'           => 'required|integer',
            'bank_name'           => 'required',
            'ifsc_code'           => 'required',
            'account_number'      => 'required',
            'account_holder_name' => 'required',
            'account_gmail'       => 'required',
            'address'             => 'required',
            'branch'              => 'required',
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $seller_bank = SellerBank::where('seller_id',$request->seller_id)->first();
        if(!empty($seller_bank))
        {
            SellerBank::where('seller_id',$request->seller_id)->update(['status'=>BANK_STATUS_DEACTIVE]);
        }
        $obj                    = new SellerBank;
        $obj->seller_id         = isset($request->seller_id) ? $request->seller_id : '';
        $obj->bank_name         = isset($request->bank_name) ? $request->bank_name : '';
        $obj->ifsc_code         = isset($request->ifsc_code) ? $request->ifsc_code : '';
        $obj->account_number    = isset($request->account_number) ? $request->account_number : '';
        $obj->account_holder_name      = isset($request->account_holder_name) ? $request->account_holder_name : '';
        $obj->account_gmail      = isset($request->account_gmail) ? $request->account_gmail : '';
        $obj->address           = isset($request->address) ? $request->address : '';
        $obj->branch            = isset($request->branch) ? $request->branch : '';
        $obj->status            = BANK_STATUS_DEACTIVE;
        $obj->save();

        return $this->sendResponse([],'Store created successfully.');
       
    }

    public function bankdetails(Request $request)
    {
         $validator = Validator::make($request->all(), [

            'seller_id'           => 'required|integer',
           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        
        $SellerBank   = SellerBank::where('seller_id',$request->seller_id)->get();
        $data =[];
        foreach($SellerBank as $value)
        {
            array_push($data,[
            'seller_id'=> isset($value->seller_id) ? $value->seller_id : '',
            'bank_name'=> isset($value->bank_name) ? $value->bank_name : '',
            'ifsc_code'=> isset($value->ifsc_code) ? $value->ifsc_code : '',
            'account_number'=> isset($value->account_number) ? $value->account_number : '',
            'account_holder_name'=> isset($value->account_holder_name) ? $value->account_holder_name : '',
            'account_gmail'=> isset($value->account_gmail) ? $value->account_gmail : '',
            'address'=> isset($value->address) ? $value->address : '',
            'branch'=> isset($value->branch) ? $value->branch : '',
            'status'=> $value->status,
            ]);
        }
       
        
        return $this->sendResponse($data,'Bankdetails details.');
    }

     public function savesellerUpidetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'           => 'required|integer',
           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        
        $obj                    = new SellerUipdetails;
        if(isset($request->google_pay_id))
        {
            $obj->google_pay_id  = isset($request->google_pay_id) ? $request->google_pay_id : '';
           
        }
        if(isset($request->phone_pay_id))
        {
            $obj->phone_pay_id   = isset($request->phone_pay_id) ? $request->phone_pay_id : '';
           
        }
        if(isset($request->paytm_id))
        {
            $obj->paytm_id       = isset($request->paytm_id) ? $request->paytm_id : '';
          
        }
        if(isset($request->others_id))
        {
            $obj->others_id      = isset($request->others_id) ? $request->others_id : '';
           
        }

        if(isset($request->active_upi))
        {
            $obj->active_upi      = isset($request->active_upi) ? $request->active_upi : '';
           
        }
        $obj->seller_id      = isset($request->seller_id) ? $request->seller_id : '';
        $obj->save();

        return $this->sendResponse([],'Seller Upi details save successfully.');
       
    }

    public function sellerupidetails(Request $request)
    {
         $validator = Validator::make($request->all(), [

            'seller_id'           => 'required|integer',
           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        
        $SellerUpi   = SellerUipdetails::where('seller_id',$request->seller_id)->get();
        $data =[];
        foreach($SellerUpi as $value)
        {
            array_push($data,[
            'seller_id'=> isset($value->seller_id) ? $value->seller_id : '',
            'google_pay_id'=> isset($value->google_pay_id) ? $value->google_pay_id : '',
            'google_pay_isverify'=> isset($value->google_pay_isverify) ? $value->google_pay_isverify : '',
            'phone_pay_id'=> isset($value->phone_pay_id) ? $value->phone_pay_id : '',
            'phone_pay_isverify'=> isset($value->phone_pay_isverify) ? $value->phone_pay_isverify : '',
            'paytm_id'=> isset($value->paytm_id) ? $value->paytm_id : '',
            'paytm_isverify'=> isset($value->paytm_isverify) ? $value->paytm_isverify : '',
            'others_id'=> isset($value->others_id) ? $value->others_id : '',
            'others_verify'=> isset($value->others_verify) ? $value->others_verify : '',
            'active_upi'=> isset($value->active_upi) ? $value->active_upi : '',
            ]);
        }
       
        
        return $this->sendResponse($data,'Seller Upi details successfully.');
    }

    public function themeslist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'           => 'required|integer',
           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $obj = Themes::get();  
        $newdata = array();
        foreach ($obj as $key=>$value) 
        {
            array_push($newdata,[
                'id'=>isset($value->id)?$value->id:'',
                "name"=> isset($value->name)?$value->name:'',
                "theme_image"=> isset($value->theme_image)?THEMES_IMAGE_URL.$value->theme_image:'',
                "theme_folder_name"=> isset($value->theme_folder_name)?$value->theme_folder_name:'',
                "created_at"=> isset($value->created_at)?$value->created_at:'',
            ]);
        } 

        return $this->sendResponse($newdata,'Store created successfully.');
       
    }

    public function updatethemes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'           => 'required|integer',
            'theme_folder_name'   => 'required',
           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        Sellers::where('id',$request->seller_id)->update(['theme'=>$request->theme_folder_name]);
        return $this->sendResponse([],'Theme update successfully.');
       
    }

    public function purchaseplan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',
            'plan_id'     => 'required|integer',
            'transation_id'=> 'required',
            'amount'     => 'required',
           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $obj                    = new Purchaseplan;
        $obj->seller_id         = isset($request->seller_id) ? $request->seller_id : '';
        $obj->plan_id           = isset($request->plan_id) ? $request->plan_id : '';
        $obj->transation_id     = isset($request->transation_id) ? $request->transation_id : '';
        $obj->amount    = isset($request->amount) ? $request->amount : '';
        $obj->save();

        if(!empty($request->refferal_by))
        {
           $setting =  Setting::select('value')->where('key_value','coin')->first();
           $total_coin = $user_detail->coin+$setting->value;
          

           $affilateuser = User::where('affiliate_id',$request->refferal_by)->first();
           if(!empty($affilateuser))
           {
              $affiliate_amount = 1000+$affilateuser->affiliate_amount;
              User::where('refferal_id',$request->refferal_by)->update(['affiliate_amount'=>$affiliate_amount]);

            }else
            {
                 Sellers::where('refferal_id',$request->refferal_by)->update(['coin'=>$total_coin]);
            }
           
           
        }

        Sellers::where('id',$request->seller_id)->update(['package_type'=>$request->plan_id]);

        return $this->sendResponse([],'Plan purchase successfully.');
    }

    public function customdomain(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',
            'domain_url'  => 'required', 
            'provider_name'  => 'required', 
            'username'  => 'required', 
            'password'  => 'required', 
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $data = Sellerdomains::where('seller_id',$request->seller_id)->where('domain_url',$request->domain_url)->first();
        if(!empty($data))
        {
        	$obj            =    new Sellerdomains;
	        $obj->seller_id =    $request->seller_id;
	        $obj->domain_url =    $request->domain_url;
	        $obj->provider_name =    $request->provider_name;
	        $obj->username =    $request->username;
	        $obj->password =    $request->password;
	        $obj->save();
       }else
        {
        	$obj = Sellerdomains::where('seller_id',$request->seller_id)->update(['domain_url'=>$request->domain_url,'provider_name'=>$request->provider_name,'username'=>$request->username,'password'=>$request->password]);
        }
        
        $newdata = Sellerdomains::where('seller_id',$request->seller_id)->first();
       
        return $this->sendResponse($newdata,'Seller Domain added successfully.');
    }

    public function getcustomdomain(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',
           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $obj            =    Sellerdomains::where('seller_id',$request->seller_id)->get();
        return $this->sendResponse($obj,'Seller Domain data.');

    }
    public function inventoryreport(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer', 
            'product_id'  => 'required|integer', 
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        // check user details
        $user_detail = Sellers::find($request->seller_id);
       
       if(empty($user_detail)){
            return $this->sendError('User does not exist.', []);
        }

        $product_data = Products::with('product_image','productsvariations')->where('id',$request->product_id)->get();

        $newdata = array();
        foreach ($product_data as $key => $catalogue) {

            $product_image = $catalogue->product_image;
            $productsvariations = isset($catalogue->productsvariations)?$catalogue->productsvariations:'';

            $prod_img =[];
            foreach($product_image as $val)
            {
                $prod_img[] = ['id'=>$val['id'],'image'=>PRODUCT_URL.$val['image']];
            }
            
            $prod_variation =[];
            if(!empty($productsvariations))
            {
                foreach($productsvariations as $pro_var_val)
                {
                    
                    $prod_variation[] = ['id'=>isset($pro_var_val['id'])?$pro_var_val['id']:'','image'=>isset($pro_var_val['image'])?PRODUCT_URL.$pro_var_val['image']:'','product_id'=>isset($pro_var_val['product_id'])?$pro_var_val['product_id']:'','sku'=>isset($pro_var_val['sku'])?$pro_var_val['sku']:'','price'=>isset($pro_var_val['price'])?$pro_var_val['price']:'','discount_price'=>isset($pro_var_val['discount_price'])?$pro_var_val['discount_price']:'','product_stock_qty'=>isset($pro_var_val['product_stock_qty'])?$pro_var_val['product_stock_qty']:'','total_stock'=>isset($pro_var_val['total_stock'])?$pro_var_val['total_stock']:'','attr_value1'=>isset($pro_var_val['attr_value1'])?$pro_var_val['attr_value1']:'','attr_value2'=>isset($pro_var_val['attr_value2'])?$pro_var_val['attr_value2']:'','attr_value3'=>isset($pro_var_val['attr_value3'])?$pro_var_val['attr_value3']:'','attr_value4'=>isset($pro_var_val['attr_value4'])?$pro_var_val['attr_value4']:''];
                }
            }

            array_push($newdata,[
                                'id'=>isset($catalogue->id)?$catalogue->id:'',
                                "seller_id"=> isset($catalogue->seller_id)?$catalogue->seller_id:'',
                                "catalogue_id"=> isset($catalogue->catalogue_id)?$catalogue->catalogue_id:'',
                                "name"=> isset($catalogue->name)?$catalogue->name:'',
                                "sku"=> isset($catalogue->sku)?$catalogue->sku:'',
                                "image"=> isset($prod_img)?$prod_img:'',
                                "description"=> isset($catalogue->description)?$catalogue->description:'',
                                "price"=> isset($catalogue->price)?$catalogue->price:'',
                                "product_stock_qty"=> isset($catalogue->product_stock_qty)?$catalogue->product_stock_qty:'',
                                'total_stock'=>isset($catalogue->total_stock)?$catalogue->total_stock:'',
                                "discount_price"=> isset($catalogue->discount_price)?$catalogue->discount_price:'',
                                "status"=> isset($catalogue->status)?$catalogue->status:'',
                                "created_at"=> isset($catalogue->created_at)?$catalogue->created_at:'',
                                "updated_at"=> isset($catalogue->updated_at)?$catalogue->updated_at:'',
                                "deleted_at"=> isset($catalogue->deleted_at)?$catalogue->deleted_at:'',
                                "prod_variation"=>isset($prod_variation)?$prod_variation:''
                            ]);
        }

        return $this->sendResponse($newdata,'Product list.');
    }

    public function customerreport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer' 
            
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

        $orderdetails = Orders::with('users')->where('seller_id',$request->seller_id);

        if($request->customer_id)
        {
            $orderdetails->where('user_id',$request->customer_id);
        }

        $data =  $orderdetails->orderBy('id','desc')->get();
        
        $newdata = [];
        foreach ($data as $ordervalue) {

            $cartdata = [];
                if(!empty($ordervalue->cart_value))
                {
                   foreach (unserialize($ordervalue->cart_value) as $key => $value) {
                        array_push($cartdata,['id'=>isset($value['product']['id'])?$value['product']['id']:'','name'=>isset($value['product']['name'])?$value['product']['name']:'','quantity'=>isset($value['quantity'])?$value['quantity']:'','price'=>isset($value['product']['price'])?$value['product']['price']:'','image'=>isset($value['product']['image'])?PRODUCT_URL.$value['product']['image']:'']);
                    } 
                }

            $users = $ordervalue->users;
            $user_data = [ "id"=> isset($users['id'])?$users['id']:'',
                "name"=> isset($users['name'])?$users['name']:'',
                "first_name"=> isset($users['first_name'])?$users['first_name']:'',
                "last_name"=> isset($users['last_name'])?$users['last_name']:'',
                "email"=> isset($users['email'])?$users['email']:'',
                "phone"=> isset($users['phone'])?$users['phone']:'',
                "image"=> isset($users['image'])?$users['image']:'',
                "role"=> isset($users['role'])?$users['role']:'',
                "seller_id"=> isset($users['seller_id'])?$users['seller_id']:'',
                "affiliate_id"=> isset($users['affiliate_id'])?$users['affiliate_id']:'',
                "affiliate_amount"=> isset($users['affiliate_amount'])?$users['affiliate_amount']:'',
                "email_verified_at"=> isset($users['email_verified_at'])?$users['email_verified_at']:'',
                "expire_link"=> isset($users['expire_link'])?$users['expire_link']:'',
                "reset_password"=> isset($users['reset_password'])?$users['reset_password']:'',
                "provider"=> isset($users['provider'])?$users['provider']:'',
                "provider_id"=> isset($users['provider_id'])?$users['provider_id']:'',
                "total_amount"=> isset($ordervalue->price)?$ordervalue->price:'0',
                ];

            array_push($newdata,['users'=>$user_data,'order_product'=>$cartdata]);
        }

        return $this->sendResponse($newdata,'Customer report list.');
    }  

    public function statchart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',      
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }
        
        /*$orderdetails = Orders::query();
        $sellerCustomer = SellerCustomer::query();
        $sellerStaff = SellerStaff::query();*/
        $condition = '';
        if(!empty($request->filter))
        {
            if($request->filter=='weekly')
            {
                $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -7 day'));
                $orderdetails = Orders::where('seller_id',$request->seller_id)->where('created_at','>=',$condition)->get();
                $sellerCustomer = SellerCustomer::where('seller_id',$request->seller_id)->where('created_at','>=',$condition)->get();
                $sellerStaff=SellerStaff::where('seller_id',$request->seller_id)->where('created_at','>=',$condition)->get();
            }
            
            if($request->filter=='thismonth')
            {
                 $startdate = date('Y-m-01');
                 $enddate   = date('Y-m-d');
                
                $orderdetails = Orders::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
                $sellerCustomer=SellerCustomer::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
                $sellerStaff=SellerStaff::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
            }
            
            if($request->filter=='lastmonth')
            {
                $startdate = date('Y-m-01', strtotime("-1 month"));
                $enddate = date('Y-m-31', strtotime("-1 month"));
                $orderdetails= Orders::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
                $sellerCustomer=SellerCustomer::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
                $sellerStaff=SellerStaff::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
            }
            if($request->filter=='thisyear')
            {
               $startdate = date('Y-01-01');
                $enddate = date('Y-12-31');
                $orderdetails = Orders::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
                $sellerCustomer=SellerCustomer::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
                $sellerStaff=SellerStaff::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
            }
            if($request->filter=='lastyear')
            {
                 $startdate = date('Y-01-01',strtotime("-1 year"));
                $enddate = date('Y-12-31',strtotime("-1 year"));
                
                $orderdetails = Orders::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
                $sellerCustomer=SellerCustomer::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
                $sellerStaff=SellerStaff::where('seller_id',$request->seller_id)->whereBetween('created_at',[$startdate,$enddate])->get();
            }
        }else{
             
            $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -7 day'));
            
            $orderdetails = Orders::where('seller_id',$request->seller_id)->where('created_at','>=',$condition)->get();
            $sellerCustomer = SellerCustomer::where('seller_id',$request->seller_id)->where('created_at','>=',$condition)->get();
            $sellerStaff = SellerStaff::where('seller_id',$request->seller_id)->where('created_at','>=',$condition)->get();
           
        }
        
        $order_count = count($orderdetails);
        $sellercustomer_count = count($sellerCustomer);
        $sellerstaff_count = count($sellerStaff);
        

        //
        /*$condition = '';
        if(!empty($request->sort_by))
        {
            if($request->sort_by=='today')
            {

                $condition = date('Y-m-d');
            }
            if($request->sort_by=='weekly')
            {

                $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -8 day'));
            }
            if($request->sort_by=='last200')
            {
               $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -200 day'));
            }
            if($request->sort_by=='last1000')
            {
               $condition = date('Y-m-d', strtotime(date('Y-m-d') .' -1000 day'));
            }

            $totalorder = Orders::where('seller_id',$request->seller_id)->where('created_at','>=',$condition)->get();
            
        }else
        {
            $totalorder = Orders::where('seller_id',$request->seller_id)->where('created_at','>=',$condition)->get();
        }

        $order_data = [];
        $total_order_price = 0;
        
        foreach ($totalorder as $key => $value_bss) {
            $total_order_price += $value_bss['price'];
           
            array_push($order_data,["price"=> isset($value_bss['price'])?$value_bss['price']:'',"created_at"=>isset($value_bss['created_at'])?$value_bss['created_at']:'']);
        }*/


        return $this->sendResponse(['order_count'=>$order_count,'sellercustomer_count'=>$sellercustomer_count,'sellerstaff_count'=>$sellerstaff_count/*,'order_data'=>['total_order_price'=>$total_order_price,"order_record"=>$order_data]*/],'statchart data.');
    }

    public function deliverypartner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',      
            'store_name'  => 'required',      
            'store_url'   => 'required',      
            'seller_email_address' => 'required',      
            'contact_information'  => 'required',      
            'pick_drop_address'    => 'required',      
            'payment_mode'         => 'required',      
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

        $obj = new Sellerdelivery;
        $obj->seller_id = $request->seller_id;
        $obj->store_name = $request->store_name;
        $obj->store_url = $request->store_url;
        $obj->seller_email_address = $request->seller_email_address;
        $obj->contact_information = $request->contact_information;
        $obj->pick_drop_address = $request->pick_drop_address;
        $obj->payment_mode = $request->payment_mode;
        $obj->status = 'pending';
        $obj->save();
        return $this->sendResponse($obj,'Delivery partner data.');
    }
    
    public function sellershoptime(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',      
               
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

        $storedata = Stores::where('seller_id',$request->seller_id)->first();
        $obj = Shopworkinghours::where('store_id',$storedata->id)->get();
      
        return $this->sendResponse($obj,'Seller shop time data.');
    }   

    public function createsellershoptime(Request $request)
    {
        $all = $request->all();
        $validator = Validator::make($request->all(), [
            'seller_id'     => 'required|integer',      
               
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

        $storedata = Stores::where('seller_id',$request->seller_id)->first();
       

        $data = $all['week_id'];
        
        if(!empty($data))
        {
            
            for ($i=1; $i <= 7; $i++) { 

                $obj  = new Shopworkinghours;
                $obj->store_id = $storedata->id;
                $obj->week_id = $i;
                $obj->start_time = isset($data[$i]['start_time'])?$data[$i]['start_time']:'Closed';
                $obj->close_time = isset($data[$i]['end_time'])?$data[$i]['end_time']:'Closed';
                $obj->save();
                
            }
        }
      
        return $this->sendResponse($obj,'Seller shop time created successfully.');
    }

    public function sellerordergenrate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',      
            'customer_id' => 'required|integer',            
            'product_id'  => 'required|integer',            
            'quantity'    => 'required|integer'            
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

        $productdata  =  Products::find($request->product_id);
        $product_price = $productdata->price;
        $product_stock_qty = $productdata->product_stock_qty;
        $total = $product_price*$request->quantity;
        
        if($product_stock_qty < $request->quantity && $product_stock_qty!='unlimited')
        {
            return $this->sendError('Product quantity must be less than '.$product_stock_qty, []);
        }else{
        
        if($product_stock_qty!='unlimited')
        {
            $remaining_quantity = $product_stock_qty-$request->quantity;
        }else{
            $remaining_quantity = $request->quantity;
        }
        
        $newdata_hh = ['seller_id'=>$request->seller_id,'user_id'=>$request->customer_id,'product_id'=>$request->product_id,'quantity'=>$request->quantity,'price'=>$product_price,'product'=>$productdata];
     
        $obj = new Orders;
        $obj->seller_id = $request->seller_id;
        $obj->user_id   = $request->customer_id;
        $obj->seller_id = $request->seller_id;
        $obj->cart_value = serialize($newdata);
        $obj->save();

        Products::where('id',$request->product_id)->update(['product_stock_qty'=>$remaining_quantity]);

        return $this->sendResponse($newdata_hh,'Seller shop time data.');
         }
        
    }


    public function globalproductlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',             
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

        $data = Products::with('product_image','productsvariations')->where('seller_id',$request->seller_id)->orderBy('id', 'DESC')->get();

        $newdata = array();
        foreach ($data as $key => $catalogue) {

            $product_image = $catalogue->product_image;
            $productsvariations = isset($catalogue->productsvariations)?$catalogue->productsvariations:'';

            $prod_img =[];
            foreach($product_image as $val)
            {
                $prod_img[] = ['id'=>$val['id'],'image'=>PRODUCT_URL.$val['image']];
            }
            
            $prod_variation =[];
            if(!empty($productsvariations))
            {
                foreach($productsvariations as $pro_var_val)
                {
                    
                    $prod_variation[] = ['id'=>isset($pro_var_val['id'])?$pro_var_val['id']:'','image'=>isset($pro_var_val['image'])?PRODUCT_URL.$pro_var_val['image']:'','product_id'=>isset($pro_var_val['product_id'])?$pro_var_val['product_id']:'','sku'=>isset($pro_var_val['sku'])?$pro_var_val['sku']:'','price'=>isset($pro_var_val['price'])?$pro_var_val['price']:'','discount_price'=>isset($pro_var_val['discount_price'])?$pro_var_val['discount_price']:'','product_stock_qty'=>isset($pro_var_val['product_stock_qty'])?$pro_var_val['product_stock_qty']:'','total_stock'=>isset($pro_var_val['total_stock'])?$pro_var_val['total_stock']:'','attr_value1'=>isset($pro_var_val['attr_value1'])?$pro_var_val['attr_value1']:'','attr_value2'=>isset($pro_var_val['attr_value2'])?$pro_var_val['attr_value2']:'','attr_value3'=>isset($pro_var_val['attr_value3'])?$pro_var_val['attr_value3']:'','attr_value4'=>isset($pro_var_val['attr_value4'])?$pro_var_val['attr_value4']:''];
                }
            }

            array_push($newdata,[
                                'id'=>isset($catalogue->id)?$catalogue->id:'',
                                "seller_id"=> isset($catalogue->seller_id)?$catalogue->seller_id:'',
                                "catalogue_id"=> isset($catalogue->catalogue_id)?$catalogue->catalogue_id:'',
                                "name"=> isset($catalogue->name)?$catalogue->name:'',
                                "sku"=> isset($catalogue->sku)?$catalogue->sku:'',
                                "image"=> isset($prod_img)?$prod_img:'',
                                "description"=> isset($catalogue->description)?$catalogue->description:'',
                                "price"=> isset($catalogue->price)?$catalogue->price:'',
                                "product_stock_qty"=> isset($catalogue->product_stock_qty)?$catalogue->product_stock_qty:'',
                                'total_stock'=>isset($catalogue->total_stock)?$catalogue->total_stock:'',
                                "discount_price"=> isset($catalogue->discount_price)?$catalogue->discount_price:'',
                                "status"=> isset($catalogue->status)?$catalogue->status:'',
                                "created_at"=> isset($catalogue->created_at)?$catalogue->created_at:'',
                                "updated_at"=> isset($catalogue->updated_at)?$catalogue->updated_at:'',
                                "deleted_at"=> isset($catalogue->deleted_at)?$catalogue->deleted_at:'',
                                "prod_variation"=>isset($prod_variation)?$prod_variation:''
                            ]);
        }

        return $this->sendResponse($newdata,'Seller product data.');
    }

    public function uploadexcelfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',             
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

        if($request->hasFile('excel_file')){

            $file = $request->file('excel_file');
            $filename = rand().'.'.$file->getClientOriginalExtension();
            if($file->getClientOriginalExtension()!='csv')
            {
                return $this->sendError('file type must be csv.', []); 
            }
            $file->move(PRODUCT_EXCEL_FILE_ROOT_PATH,$filename);

            $obj = new Productexcelfile;
            $obj->seller_id = $request->seller_id;
            $obj->file_name = $filename;
            $obj->save();

            $inserted_id  = $obj->id;

            $productexcelfile = Productexcelfile::find($inserted_id);
            $file_data = fopen(PRODUCT_EXCEL_FILE_ROOT_PATH.$productexcelfile->file_name,"r");
            fgetcsv($file_data);
           while(($line = fgetcsv($file_data)) !== FALSE){
               
                $catalogue_id   = $line[0];
                $productname  = $line[1];
                $sku  = $line[2];
                $description = $line[3];
                $price = $line[4];
                $stockquantity = $line[5];
                $discount_price = $line[6];
                $unit = $line[7];
                $image = $line[8];


                $obj                = new Products; 
                $obj->seller_id     =   $request->seller_id;
                $obj->catalogue_id  =  $catalogue_id;
                $obj->name          =   $productname;
                $obj->sku           =   $sku;
                $obj->description   =   $description;
                $obj->price         =   $price;
                $obj->product_stock_qty =   $stockquantity;
                $obj->total_stock   =   $stockquantity;
                $obj->unit          =   $unit;
                $obj->discount_price =   $discount_price;
                $obj->status        =   ACTIVE;
                $obj->save();

                $product_insert_id = $obj->id;
                $imag_arr = explode(',', $image);
                
                foreach ($imag_arr as $value) {

                    if (!empty($value)) {
                        file_put_contents(
                         PRODUCT_ROOT_PATH. basename($value),
                            file_get_contents($value)
                        );
                    }

                    $product_img             =   new Productsimage;
                    $product_img->product_id =   $product_insert_id;
                    $product_img->image      =   basename($value);
                    $product_img->save();

                }
            }
            fclose($file_data);
              
        }

        return $this->sendResponse([],'product uploaded Successfully.');
    }

    public function createsellerslider(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',             
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

        if($request->hasfile('image'))
        {
            $image = $request->file('image');
            $img_arr = [];
            $i = 1;

            foreach ($image as $value) {

                $img_name =time().rand(99,100).$i++.'.'.$value->getClientOriginalExtension();
                $value->move(SLIDER_ROOT_PATH, $img_name); 

                $obj = new Sellerslider;
                $obj->seller_id = $request->seller_id;
                $obj->image  = $img_name;
                $obj->save();
            }
        }

        return $this->sendResponse([],'Seller slider created Successfully.');

    }

    public function deletesellerslider(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',             
            'slider_id'   => 'required|integer',             
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

       
        $obj = Sellerslider::find($request->slider_id)->delete();
       
        return $this->sendResponse([],'Seller slider deleted Successfully.');

    }

    public function Contactus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',             
                     
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

        $obj = Contactus::where('seller_id',$request->seller_id)->get();
       
        return $this->sendResponse($obj,'Contactus List.');
      
    }

    public function Newsletter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',            
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

       
        $obj = Newsletter::where('seller_id',$request->seller_id)->get();
       
        return $this->sendResponse($obj,'Newsletter List.');

    }

    public function cartuser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',            
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

       
        $Cart_data = Cart::select('user_id')->where('seller_id',$request->seller_id)->groupBy('user_id')->get();

       $array_data = [];
       foreach ($Cart_data as $key => $value) {
           $user =  User::find($value->user_id);
          array_push($array_data,['user_name'=>isset($user->name)?$user->name:'','user_id'=>isset($value->user_id)?$value->user_id:'']);
       }
        return $this->sendResponse($array_data,'Cart User.');

    }

    public function cartproductdetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',            
            'user_id'   => 'required|integer',            
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

       
        $Cart_data = Cart::with('product','user')->where('seller_id',$request->seller_id)->where('user_id',$request->user_id)->get();
       
       $array_data = [];
       foreach ($Cart_data as $key => $value) {
            $cart_data=[
                "id"=>$value->id,
                "session_id"=>isset($value->session_id)?$value->session_id:'',
                "seller_id"=>isset($value->seller_id)?$value->seller_id:'',
                "user_id"=>isset($value->user_id)?$value->user_id:'',
                "product_id"=>isset($value->product_id)?$value->product_id:'',
                "product_variation_id"=>isset($value->product_variation_id)?$value->product_variation_id:'',
                "quantity"=>isset($value->quantity)?$value->quantity:'',
                "price"=>isset($value->price)?$value->price:'',
                "created_at"=>isset($value->created_at)?$value->created_at:'',
            ];

            if(!empty($value->product))
            {
                $product_data=[
                "id"=>$value->product['id'],
                "prduct_name"=>isset($value->product['name'])?$value->product['name']:'',
                "sku"=>isset($value->product['sku'])?$value->product['sku']:'',
                "image"=>isset($value->product['image'])?PRODUCT_URL.$value->product['image']:'',
                "description"=>isset($value->product['description'])?$value->product['description']:'',
                "type"=>isset($value->product['type'])?$value->product['type']:'',
                "price"=>isset($value->product['price'])?$value->product['price']:'',
                    "discount_price"=>isset($value->product['discount_price'])?$value->product['discount_price']:'',
                ];
            }else
            {
                $product_data =[];
            }
            
            if(!empty($value->user))
            {
                $user_data=[
                            "id"=>isset($value->user['id'])?$value->user['id']:'',
                            "user_name"=>isset($value->user['name'])?$value->user['name']:'',
                            "first_name"=>isset($value->user['first_name'])?$value->user['first_name']:'',
                            "last_name"=>isset($value->user['last_name'])?$value->user['last_name']:'',
                            "email"=>isset($value->user['email'])?$value->user['email']:'',
                         
                        ];
            }else
            {
                $user_data=[];
            }
            

          array_push($array_data,['cart_data'=>$cart_data,'product_data'=>$product_data,'user_data'=>$user_data]);
       }
        return $this->sendResponse($array_data,'Cart User.');

    }

    public function countrieslist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

       
        $Countries = Countries::get();
       
        return $this->sendResponse($Countries,'Country List.');

    }

    public function Statelist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

       
        $States = States::get();
       
        return $this->sendResponse($States,'States List.');

    }

    public function citieslist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id'   => 'required|integer',           
            'state_id'   => 'required|integer',           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = Sellers::find($request->seller_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }

       
        $Cities = Cities::where('state_id',$request->state_id)->get();
       
        return $this->sendResponse($Cities,'Cities List.');

    }

    public function sellerstaffprofile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_staff_id'   => 'required|integer',           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = SellerStaff::find($request->seller_staff_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }
        
        $data =[ "id"=> isset($user_detail->id)?$user_detail->id:'',
        "seller_id"=>isset($user_detail->seller_id)?$user_detail->seller_id:'',
        "phone"=> isset($user_detail->phone)?$user_detail->phone:'',
        "name"=> isset($user_detail->name)?$user_detail->name:'',
        "image"=> isset($user_detail->image)?STAFF_LOGO_URL.$user_detail->image:'',
        "permission"=> isset($user_detail->permission)?$user_detail->permission:'',
        "status"=> isset($user_detail->status)?$user_detail->status:'',
        "created_at"=>isset($user_detail->created_at)?$user_detail->created_at:''];


        return $this->sendResponse($data,'Seller staff profile.');

    }

    public function packagepermision(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_staff_id'   => 'required|integer',           
            'seller_package_id' => 'required|integer',           
        ]);

        if($validator->fails()){
            $error = $validator->errors()->first();
            return $this->sendError($error,[]);
        }

        $user_detail = SellerStaff::find($request->seller_staff_id);
       
        if(empty($user_detail)){            
            return $this->sendError('User does not exist.', []);
        }
       
        if($request->seller_package_id==1)
        {
            $data = ['1'=>'Member Signup Free','2'=>'Create Catalog','3'=>'Add Unlimited Product','4'=>'Get the website of your store','5'=>'Unlimited Product Sharing'];
        }
        if($request->seller_package_id==2)
        {

            $data = ['1'=>'All Features of the free plan','2'=>'Add staff member
            ','3'=>'Add Customers and can create orders for them','4'=>'Add Facebook, Instagram thinking to increase the sale','5'=>'Upload documents to make the verified seller','6'=>'Unlock referral program to get the MMM coins for the next purchase'];
        }
        if($request->seller_package_id==3)
        {
            
            $data = ['1'=>'All features of VIP','2'=>'Custom domain linking
            ','3'=>'Get the delivery facilities from MMM','4'=>'Create Business Card and unlimited sharing on social media','5'=>'Create the promo codes for users','6'=>'Can access all theme design'];
        }
        return $this->sendResponse($data,'permission according package.');

    }
}