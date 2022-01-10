<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\BaseController as BaseController;
use App\Http\Controllers\Controller;
use App\Sellers;
use App\Payments;
use App\Orders;
use App\Products;
use App\Category;
use App\Catalogue;
use App\Contactus;
use App\CatalogueAttributes;
use App\CatalougeVariations;
use App\Productsimage;
use App\Productsvariations;
use App\Shopworkinghours;
use App\Stores;
use App\Offers;
use App\StoreType;
use App\Sellerdelivery;
use App\Newsletter;
use App\SellerBank;
use App\SellerUipdetails;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class SellersController extends BaseController
{
    
    public $model   =   'Sellers';
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
     * Show Sellerslist.
     *
     */
    public function listSellers()
    {
        $modeldata = Sellers::get();
        return  View::make("admin.$this->model.index",compact('modeldata')); 
    }

    /*Get All Sellers As A JSON Data*/
    public function sellersJsonData()
    {
        $data = Sellers::select('id','email', 'phone', 'status', 'is_verify')->orderBy('id', 'DESC')->get();

        return response()->json($data);
    } 
    /*End Get All Sellers As A JSON Data*/
    /**
        * Function for display page  for Seller Payments Page 
        *
        * @param null
        *
        * @return payments page. 
    */
    public function listSellerPayments($Id=0)
    {
        
        return  View::make("admin.$this->model.payments",compact('Id'));
       
    }
    //List Seller Payment Json Data
    public function listSellerPaymentJsonData($Id=0)
    {

	    $data = Payments::with('users','sellers')->where('seller_id', $Id)->get();

        return response()->json($data);
    }

    public function viewSellerPayment($Id=0)
    {
        $modeldata    =  Payments::with('users','sellers')->find($Id);
        return  View::make("admin.$this->model.viewpayment",compact('modeldata'));
    }

    /**
        * Function for display page  for Seller Payments Page 
        *
        * @param null
        *
        * @return payments page. 
    */
    public function listSellerOrders($Id=0)
    { 
        return  View::make("admin.$this->model.orders",compact('Id'));
    }

    /**
        * Function for display page  for Seller Payments Page 
        *
        * @param null
        *
        * @return payments page. 
    */
    public function listSellerOrdersJsonData($Id=0)
    {   
        $data = Orders::with('sellers','users')->where('seller_id', $Id)->get();
        return response()->json($data);
        
    }

    /**
        * Function for display page  for Seller Payments Page 
        *
        * @param null
        *
        * @return payments page. 
    */
    public function listSellerStores($Id = 0)
    {
        
        $modeldata    =  Stores::with('storetype')->find($Id);

        return  View::make("admin.$this->model.stores",compact('modeldata'));

    }

    /**
        * Function for display page  for add new Sellers page 
        *
        * @param null
        *
        * @return view page. 
    */
    public function addSellers(){

        return  View::make("admin.$this->model.add");
    } //end addAdvertisement()

    /*Random String function*/

    private function random_string($length = 6)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+/*|\-.';

        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }

    public function saveSellers()
    {

        $request = Request::all();
        
    	$validator  =   Validator::make(
            Request::all(),
            array(
                'email'              => 'required|unique:sellers',
                'gst_no'             => 'required|unique:sellers,gst',
                'contact'            => 'required|unique:sellers,phone',
                'address'            => 'required',
                'name'               => 'required',
            )
        );
        $records = Sellers::get();
        $count = 10000;
        if(!empty($records))
        {
            for ($i=0; $i < count($records); $i++) { 
                $count++;
            }   
        }

       
        if ($validator->fails())
        {   
            
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
       
            $social_id = isset($request['social_id']) ? $request['social_id'] : ''; 
            $gst_no    = isset($request['gst_no'])    ? $request['gst_no']    : 0;  
            
           // $refferal_id = $this->random_string();
            $obj                    = new Sellers;

            //Aadhar Card Image Upload Section
            if(Request::hasFile('aadhar_card')){
              
                $extension  =    Request::file('aadhar_card')->getClientOriginalExtension();
                $fileName   =   time().'-aadhar.'.$extension;
                if(Request::file('aadhar_card')->move(DOCUMENT_ROOT_PATH, $fileName)){

                    $obj->aadhar_card                =  $fileName;
                }
            }
            else
            {
                $obj->aadhar_card                    = "";
            }
            //Pan Card Image Upload Section
            if(Request::hasFile('pancard')){
                
                $extension  =    Request::file('pancard')->getClientOriginalExtension();
                $fileName   =   time().'-pancard.'.$extension;
                if(Request::file('pancard')->move(DOCUMENT_ROOT_PATH, $fileName)){

                    $obj->pan_card                =  $fileName;
                }
            }
            else
            {
                $obj->pan_card                    =  "";
            }
            //Profile Image Upload Section
            if(Request::hasFile('profile_image')){
                
                $extension  =    Request::file('profile_image')->getClientOriginalExtension();
                $fileName   =   time().'-profile.'.$extension;
                if(Request::file('profile_image')->move(DOCUMENT_ROOT_PATH, $fileName)){

                    $obj->image                =  $fileName;
                }
            }
            else
            {
                $obj->image                    =  "";
            }
        
            
            $obj->email             = $request['email'];
            $obj->phone             = $request['contact'];
            $obj->address           = $request['address'];
            $obj->name              = $request['name'];
            $obj->social_id         = $social_id;
            $obj->refferal_id       = $count;
            $obj->gst               = $gst_no;
            $obj->save();

            $last_id = $obj->id;
            
        }     

        Session::flash('success',  trans("Sellers has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete Sellers*/
    public function deleteSellers($Id=0)
    {
        $obj        =  Sellers::find($Id);
        $seller     =  Sellers::where('id',$Id)->first();

        if($obj->delete())
        {
           
            if(!empty($seller->aadhar_card))
            {
                $file_path_aadhar = DOCUMENT_ROOT_PATH.$seller->aadhar_card;
               if(File::exists($file_path_aadhar)){
                    unlink($file_path_aadhar);
                } 
            }
           
            if(!empty($seller->pan_card))
            {
                $file_path_pancard = DOCUMENT_ROOT_PATH.$seller->pan_card;
               if(File::exists($file_path_pancard)){
                    unlink($file_path_pancard);
                } 
            }
            
            if(!empty($seller->image))
            {
                $file_path_profile = DOCUMENT_ROOT_PATH.$seller->image;
               if(File::exists($file_path_profile)){
                    unlink($file_path_profile);
                } 
            }
           Session::flash('success', trans("Sellers has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete Sellers*/

    public function editSellers($Id = 0)
    {
        $modeldata    =  Sellers::find($Id);
       
        return  View::make("admin.$this->model.edit",compact('modeldata'));
    }

    public function updatebnkstatus($id=0)
    {
        $request = Request::all();
        $id = $request['id'];
        $type = $request['type'];

        if($type == 'approved')
        {
            $update = SellerBank::where('seller_id',$id)->update(['status' => 1]);
            $_message = array('status' => 'true', 'message' => 'Seller bank status Approved Successfully.');
        }
        else
        {
            $update = SellerBank::where('seller_id',$id)->update(['status' =>2]); 
            $_message = array('status' => 'true', 'message' => 'Seller bank status Successfully.');            
        }
        if($update == TRUE)
        {
            $response = $_message;
        }
        else
        {
            $response = array('status' => 'false', 'message' => 'Something went wrong');
        }
        echo json_encode($response);
    }
    public function viewSellers($Id = 0)
    {
       
        $modeldata    =  Sellers::with('stores','storetype','sellerdelivery')->where("id",$Id)->first();

        $storedata    =  (!empty($modeldata->stores)) ? $modeldata->stores : "";
        $package      =  (!empty($modeldata->storetype)) ? $modeldata->storetype : "";
        $sellerdelivery = (!empty($modeldata->sellerdelivery)) ? $modeldata->sellerdelivery : "";

        $newsletter = Newsletter::where('seller_id',$Id)->get();
        $Contactus = Contactus::where('seller_id',$Id)->get();
        $SellerBank = SellerBank::where('seller_id',$Id)->first();
        $Offers = Offers::where('seller_id',$Id)->get();
        $sellerupi = SellerUipdetails::where('seller_id',$Id)->first();
       
        if(empty($SellerBank))
        {
            $SellerBank = '';
        }
        if($modeldata->refferal_id!='')
        {
           $refferal_by  =  Sellers::where("refferal_by",$modeldata->refferal_id)->get();
           $active_seller_reff  =  Sellers::where("refferal_by",$modeldata->refferal_id)->where("status",'active')->get();
        }else
        {
            $refferal_by = '';
            $active_seller_reff = '';
        }

        $total_amount = Orders::where('seller_id',$Id)->where('status','Delivered')->where('seller_pay_status','0')->sum('price');
       
        return  View::make("admin.$this->model.view",compact('modeldata','refferal_by','active_seller_reff','storedata','package','sellerdelivery','newsletter','Contactus','SellerBank','total_amount','sellerupi','Offers'));

    }

    public function sellerDeliveredStatus()
    {
        $request = Request::all();
        $id = $request['id'];
        $type = $request['type'];

        if($type == 'approved')
        {
            $update = Sellerdelivery::where('seller_id',$id)->update(['status' => 'approved']);
            $_message = array('status' => 'true', 'message' => 'Seller Delivered Approved Successfully.');
        }
        else
        {
            $update = Sellerdelivery::where('seller_id',$id)->update(['status' => 'rejected']); 
            $_message = array('status' => 'true', 'message' => 'Seller Delivered Rejected Successfully.');            
        }
        if($update == TRUE)
        {
            $response = $_message;
        }
        else
        {
            $response = array('status' => 'false', 'message' => 'Something went wrong');
        }
        echo json_encode($response);
    }

    public function updateSellers($Id = 0)
    {
        
        $request = Request::all();
       
        $validator  =   Validator::make(
        Request::all(),
        array(

            'email'              => 'required',
            'gst_no'             => 'required',
            'contact'            => 'required',
            'address'            => 'required',
            'name'               => 'required',
        )
        );
        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

            $obj                    = Sellers::findOrfail($Id);
            $seller                 = Sellers::where('id',$Id)->first();
            

            //Aadhar Card Upload Edit Section
            if(Request::hasFile('aadhar_card')){
               $file_path = DOCUMENT_ROOT_PATH.$seller['aadhar_card'];
               if(!empty($seller['aadhar_card']))
               {
                if(File::exists($file_path)){
                    unlink($file_path);
                }
               }
                $extension  =    Request::file('aadhar_card')->getClientOriginalExtension();
                $fileName   =   time().'-aadhar.'.$extension;
                if(Request::file('aadhar_card')->move(DOCUMENT_ROOT_PATH, $fileName)){

                    $obj->aadhar_card                =  $fileName;
                }
            }
            //Pan Card Upload Edit Section
            if(Request::hasFile('pancard')){
                $file_path = DOCUMENT_ROOT_PATH.$seller['pan_card'];
                if(!empty($seller['pan_card']))
                {
                   if(File::exists($file_path)){
                        unlink($file_path);
                    } 
                }

                $extension  =    Request::file('pancard')->getClientOriginalExtension();
                $fileName   =   time().'-pancard.'.$extension;
                if(Request::file('pancard')->move(DOCUMENT_ROOT_PATH, $fileName)){

                    $obj->pan_card                =  $fileName;
                }
            }
            //Profile Image Upload Edit Section
            if(Request::hasFile('profile_image')){
                $file_path = DOCUMENT_ROOT_PATH.$seller['image'];
                if(!empty($seller['image']))
                {
                   if(File::exists($file_path)){
                        unlink($file_path);
                    } 
                }

                $extension  =    Request::file('profile_image')->getClientOriginalExtension();
                $fileName   =   time().'-profile_image.'.$extension;
                if(Request::file('profile_image')->move(DOCUMENT_ROOT_PATH, $fileName)){

                    $obj->image                =  $fileName;
                }
            }

            $obj->email             = $request['email'];
            $obj->phone             = $request['contact'];
            $obj->gst               = $request['gst_no'];
            $obj->address           = $request['address'];
            $obj->name              = $request['name'];
            $obj->save();
             
        } 
        Session::flash('success',  trans("Sellers has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }

    public function catalogueSellers($Id = 0)
    {
        return  View::make("admin.$this->model.catalogue",compact('Id'));

    }

    /**
        * Function for display page  for Seller Payments Page 
        *
        * @param null
        *
        * @return payments page. 
    */
    public function listSellerCatalogueJsonData($Id=0)
    {

        $data = Catalogue::where('seller_id', $Id)->get();
        return response()->json($data);
    }
    
    public function addSellerCatalogue($seller_id = 0)
    {

    	$store_type_details = Stores::where('seller_id',$seller_id)->first();
    	$category = Category::where('store_type_id',$store_type_details->store_type)->get();
       
    	return  View::make("admin.$this->model.addcatalogue",compact('seller_id','category'));
    }

    public function saveSellerCatalogue($seller_id = 0)
    {
    	$request = Request::all();
       
        $messages = array(
                    'name.required'          =>  trans('messages.name.REQUIRED_ERROR'),
                    'category.required'      =>  trans('messages.category.REQUIRED_ERROR'),
                    
            );

       
        $validator  =   Validator::make(
        Request::all(),
        array(
            'name'          => 'required',
            'category'      => 'required',
           
        ),$messages
        );

        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

            $obj                    = new Catalogue;
            $obj->name              = $request['name'];
            $obj->seller_id         = $seller_id;
            $obj->category_id       = $request['category'];
            $obj->status       		= CATEGORY_ACTIVE;
            $obj->save();

            $catalogue_insert_id  =  $obj->id;
            
            $att_val_arr = [];

            if(count($request['attr_name'])>1)
            {
                

                for ($i=0; $i < count($request['attr_name']); $i++) { 
                
                    $attributes[] = CatalogueAttributes::create([
                        'seller_id'         => $seller_id,
                        'catalogue_id'      => $catalogue_insert_id,
                        'attr_name'         => $request['attr_name'][$i],
                        'attr_value'        => $request['attr_value'][$i]
                    ]);
                
                array_push($att_val_arr,explode(',',$request['attr_value'][$i]));
            }

            $combinationdata =  $this->combinations($att_val_arr);

    	       foreach ($combinationdata as $combinationdata_value) {

    	           $obj = new CatalougeVariations;
    	           $obj->seller_id = $seller_id;
    	           $obj->catalouge_id = $catalogue_insert_id;
    	           $obj->attr_value1 = isset($combinationdata_value[0])?$combinationdata_value[0]:'';
    	           $obj->attr_value2 = isset($combinationdata_value[1])?$combinationdata_value[1]:'';
    	           $obj->attr_value3 = isset($combinationdata_value[2])?$combinationdata_value[2]:'';
    	           $obj->attr_value4 = isset($combinationdata_value[3])?$combinationdata_value[3]:'';
    	           $obj->save();
    	       }
           }
             
        } 
        Session::flash('success',  trans("Catalogue has been updated successfully."));  
        return Redirect::back();
    }
    public function viewSellerCatalogue($Id=0)
    {
        $modeldata = Catalogue::with('category','catalogueattributes','catalougevariations')->find($Id);
        
        return  View::make("admin.$this->model.viewcatalogue",compact('Id','modeldata'));

    }
    
    public function editSellerCatalogue($Id=0)
    {
        $modeldata = Catalogue::find($Id);
        $category_data = Category::find($modeldata->category_id);
        $category = Category::where('store_type_id',$category_data->store_type_id)->get();
        
        return  View::make("admin.$this->model.editcatalogue",compact('Id','modeldata','category'));
        
    }
    
    
    public function deleteSellerCatalogue($Id=0)
    {
        $modeldata = Catalogue::find($Id)->delete();
        Products::where('catalogue_id',$Id)->delete();
        
        Session::flash('success',  trans("Catalogue has been deleted successfully."));  
        return Redirect::back();
    }
    
    public function updateSellerCatalogue($Id = 0)
    {
        $request = Request::all();
       
        $messages = array(
                    'name.required'          =>  trans('messages.name.REQUIRED_ERROR'),
                    'category.required'       =>  trans('messages.category.REQUIRED_ERROR'),
                    
            );

       
        $validator  =   Validator::make(
        Request::all(),
        array(
            'name'          => 'required',
            'category'      => 'required',
           
        ),$messages
        );

        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

            $obj                    = Catalogue::findOrfail($Id);
            $obj->name              = $request['name'];
            $obj->category_id       = $request['category'];
            $obj->save();
             
        } 
        Session::flash('success',  trans("Catalogue has been updated successfully."));  
         return Redirect::back();
    }
    
    public function sellersProducts($Id = 0)
    {
        
        return  View::make("admin.$this->model.product", compact('Id'));

    }
    
    public function listSellerProductJsonData($Id =0)
    {

        $data = Products::where('seller_id',$Id)->get();
        return response()->json($data);
    }
    
    public function deleteSellerProduct($Id=0)
    {
        $modeldata = Products::find($Id)->delete();
        
        Session::flash('success',  trans("Product has been deleted successfully."));  
        return Redirect::back();
    }
    
    public function editSellerProduct($Id=0)
    {
        $modeldata = Products::with('productsvariations')->find($Id);
        $catalogue_list = Catalogue::where('seller_id',$modeldata->seller_id)->where('status',CATALOGUE_ACTIVE)->get();

        return  View::make("admin.$this->model.editproduct",compact('Id','modeldata','catalogue_list'));
    }
    
    public function viewSellerProduct($Id=0)
    {
        $modeldata = Products::with('product_image','productsvariations')->find($Id);
        
        return  View::make("admin.$this->model.viewproduct",compact('Id','modeldata'));
    }

    public function addSellerProduct($seller_id = 0)
    {
        
    	$catalogue_list = Catalogue::where('seller_id',$seller_id)->where('status',CATALOGUE_ACTIVE)->get();
        $sellerdata = Sellers::where('id',$seller_id)->first();
    	return  View::make("admin.$this->model.addproduct",compact('seller_id','catalogue_list','sellerdata'));
    }

    public function saveSellerProduct($seller_id = 0)
    {
    	$request = Request::all();
        
        $messages = array(
                    'catalogue_id.required' =>  trans('messages.catalogue_id.REQUIRED_ERROR'),
                    'name.required'       	=>  trans('messages.name.REQUIRED_ERROR'),
                    'sku.required'         	=>  trans('messages.sku.REQUIRED_ERROR'),
                    'description.required'  =>  trans('messages.description.REQUIRED_ERROR'),
                    'price.required'        =>  trans('messages.price.REQUIRED_ERROR'),
                    'discount_price.required' =>  trans('messages.discount_price.REQUIRED_ERROR'),
                    'product_stock_qty.required' =>  trans('messages.product_stock_qty.REQUIRED_ERROR'),
                    
            );

       
        $validator  =   Validator::make(
        $request,
        array(
            'catalogue_id'  	=> 'required',
            'name'      		=> 'required',
            'sku'      			=> 'required',
            'description'   	=> 'required',
            'price'      		=> 'required',
            'discount_price'	=> 'required',
            'product_stock_qty' => 'required',
           
        ),$messages
        );

        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

        	if(!empty($request['image']))
	        {
	            $image = $request['image'];
	            $img_arr = [];
	            $i = 1;
	            foreach ($image as $value) {

	                $img_name =time().rand(99,100).$i++.'.'.$value->getClientOriginalExtension();
	                $value->move(PRODUCT_ROOT_PATH, $img_name); 
	                $img_arr[] = $img_name;

	            }
	                 
	        }

            $filedata = '';
            if(Request::hasFile('product_file'))
            {
                $file = Request::file('product_file');
                
                $file_name =time().rand(99,100).'.'.$file->getClientOriginalExtension();
                $file->move(PRODUCT_FILE_ROOT_PATH, $file_name); 
                $filedata = $file_name;
                     
            }

            $obj                =   new Products;
	        $obj->seller_id     =   $seller_id;
	        $obj->catalogue_id  =   $request['catalogue_id'];
	        $obj->name          =   $request['name'];
	        $obj->image         =   isset($img_arr[0])?$img_arr[0]:'';
            $obj->product_file  =   isset($filedata)?$filedata:'';
            $obj->unit          =   isset($request['unit'])?$request['unit']:'';
	        $obj->sku           =   isset($request['sku'])?$request['sku']:'';
	        $obj->type          =   isset($request['type'])?$request['type']:'';
	        $obj->description   =   isset($request['description'])?$request['description']:'';
	        $obj->price         =   $request['price'];
	        $obj->product_stock_qty = $request['product_stock_qty'];
	        $obj->discount_price    =   $request['discount_price'];
	        $obj->status        =   $request['status'];
	        $obj->save();

	        $product_insert_id  =  $obj->id;
	        foreach ($img_arr as $value) {

	            $product_img             =   new Productsimage;
	            $product_img->product_id =   $product_insert_id;
	            $product_img->image      =   $value;
	            $product_img->save();

	        }
	        
	        ///save variation product
	        if(!empty($request['type']) && $request['type']=="variation")
	        {

	            $k=0;
	            for($j=0;$j<count($request['catalogue_variation_id']);$j++) {
	        		
	        		if(!empty($request['variation_image'][$j]))
				    {
	                        $img = $request['variation_image'][$j];
	                        $imagename = rand().$k++.'.'.$img->getClientOriginalExtension();
				            $img->move(PRODUCT_ROOT_PATH,$imagename);
				    }

                    if(!empty($request['variation_product_file'][$j]))
                    {
                        $_img = $request['variation_product_file'][$j];
                        $image_name = rand().$k++.'.'.$_img->getClientOriginalExtension();
                        $_img->move(PRODUCT_FILE_ROOT_PATH,$image_name);
                    }

					$CatalougeVariations = CatalougeVariations::findOrFail($request['catalogue_variation_id'][$j]);

		        	$obj                 =    new Productsvariations;
	                $obj->catalogue_id   =    $request['catalogue_id'];
				    $obj->product_id     =    $product_insert_id;
				    $obj->sku            =    $request['variation_sku'][$j];
				    $obj->price          =    $request['variation_price'][$j];
				    $obj->discount_price    =    $request['variation_discount_price'][$j];
				    $obj->product_stock_qty =    $request['variation_stock'][$j];
                    $obj->image          =    isset($imagename)?$imagename:'';
                    $obj->product_file   =    isset($image_name)?$image_name:'';
                    $obj->unit           =    $request['variation_unit'][$j];
				    $obj->attr_value1    =    isset($CatalougeVariations->attr_value1)?$CatalougeVariations->attr_value1:'';
				    $obj->attr_value2    =    isset($CatalougeVariations->attr_value2)?$CatalougeVariations->attr_value2:'';
				    $obj->attr_value3    =    isset($CatalougeVariations->attr_value3)?$CatalougeVariations->attr_value3:'';
				    $obj->attr_value4    =    isset($CatalougeVariations->attr_value4)?$CatalougeVariations->attr_value4:'';
				    $obj->save();        		
	        	}

	        }
             
        } 
        Session::flash('success',  trans("Product has been created successfully."));  
         return Redirect::back();
    }

    public function updateSellerProduct($Id = 0)
    {
    	$request = Request::all();
       
        $messages = array(
                    
                    'name.required'       	=>  trans('messages.name.REQUIRED_ERROR'),
                    'sku.required'         	=>  trans('messages.sku.REQUIRED_ERROR'),
                    'description.required'  =>  trans('messages.description.REQUIRED_ERROR'),
                    'price.required'        =>  trans('messages.price.REQUIRED_ERROR'),
                    'discount_price.required' =>  trans('messages.discount_price.REQUIRED_ERROR'),
                    'product_stock_qty.required' =>  trans('messages.product_stock_qty.REQUIRED_ERROR'),
                    
            );

       
        $validator  =   Validator::make(
        $request,
        array(
            'name'      		=> 'required',
            'sku'      			=> 'required',
            'description'   	=> 'required',
            'price'      		=> 'required',
            'discount_price'	=> 'required',
            'product_stock_qty' => 'required',
           
        ),$messages
        );

        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

            
        	if(isset($request['image']))
	        {
	            $image = $request['image'];
	            $img_arr = [];
	            $i = 1;
	            foreach ($image as $value) {

	                $img_name =time().rand(99,100).$i++.'.'.$value->getClientOriginalExtension();
	                $value->move(PRODUCT_ROOT_PATH, $img_name); 
	                $img_arr[] = $img_name;

	            }

                Productsimage::where('product_id',$Id)->delete();
                foreach ($img_arr as $value) {

                    $product_img             =   new Productsimage;
                    $product_img->product_id =   $Id;
                    $product_img->image      =   $value;
                    $product_img->save();

                }     
	        }

	        $array_req_data = [
            'name' => isset($request['name'])?$request['name']:'',
            'catalogue_id' => isset($request['catalogue_id'])?$request['catalogue_id']:'',
            'sku'   => isset($request['sku'])?$request['sku']:'',
            'type'  => isset($request['type'])?$request['type']:'',
            'image' => isset($img_arr[0])?$img_arr[0]:'',
            'price' => isset($request['price'])?$request['price']:'',
            'product_stock_qty' => isset($request['product_stock_qty'])?$request['product_stock_qty']:'',
            'description' => isset($request['description'])?$request['description']:'',
            'discount_price' => isset($request['discount_price'])?$request['discount_price']:'',
            'status' => isset($request['status'])?$request['status']:ACTIVE,
            ];

	        $array_req_data = array_filter($array_req_data);

	        Products::where('id',$Id)->update($array_req_data);

            

	       	if(!empty($request['catalogue_variation_id']))
	        {
	           
	            $k=0;
                for($j=0;$j<count($request['catalogue_variation_id']);$j++) {
                    
                    if(!empty($request['variation_image'][$j]))
                    {
                            $img = $request['variation_image'][$j];
                            $imagename = rand().$k++.'.'.$img->getClientOriginalExtension();
                            $img->move(PRODUCT_ROOT_PATH,$imagename);
                    }

                    $array_req_data_var = [

                        'sku'                =>    $request['variation_sku'][$j],
                        'price'              =>    $request['variation_price'][$j],
                        'discount_price'     =>    $request['variation_discount_price'][$j],
                        'product_stock_qty'  =>    $request['variation_stock'][$j],
                        'image'              =>    isset($imagename)?$imagename:'',
                        'attr_value1'        =>    isset($request['attr_value1'][$j])?$request['attr_value1'][$j]:'',
                        'attr_value2'        =>    isset($request['attr_value2'][$j])?$request['attr_value2'][$j]:'',
                        'attr_value3'        =>    isset($request['attr_value3'][$j])?$request['attr_value3'][$j]:'',
                        'attr_value4'        =>    isset($request['attr_value4'][$j])?$request['attr_value4'][$j]:'',
                        
                        ];

                    $array_req_data_var = array_filter($array_req_data_var);
                 
                    Productsvariations::where('id',$request['catalogue_variation_id'][$j])->update($array_req_data_var);             
                }
	        }
           
            Session::flash('success',  trans("Product has been updated successfully."));  
         	return Redirect::back();
        } 
       
    }

    public function ajaxcataloguevariation()
    {
        $catalogue_variation = CatalougeVariations::where('catalouge_id',$_POST['catalogue_id'])->get();
        
        return view("admin.$this->model.cataloguevariation",compact('catalogue_variation'))->render();
    }

    public function viewSellerorder($id=0)
    {
        $modeldata = Orders::with('sellers','users')->find($id);
        return  View::make("admin.$this->model.vieworder",compact('modeldata'));
    }

    public function deletsellerorder($id=0)
    {
        Orders::find($id)->delete();
        Session::flash('success',  trans("Order has been deleted successfully."));  
        return Redirect::back();
    }

    public function statusorderupdate()
    {
        $request = Request::all();
        $id = $request['id'];
        $type = $request['type'];

        if($type == 'Inprogress')
        {
            $update = Orders::where('id',$id)->update(['status' => 'Inprogress']);
            $_message = array('status' => 'true', 'message' => 'Order Status updated Successfully.');
        }
        if($type == 'Return')
        {
            $update = Orders::where('id',$id)->update(['status' => 'Return']);
            $_message = array('status' => 'true', 'message' => 'Order Status updated Successfully.');
        }
        if($type == 'Delivered')
        {
            $update = Orders::where('id',$id)->update(['status' => 'Delivered']);
            $_message = array('status' => 'true', 'message' => 'Order Status updated Successfully.');
        }
        if($type == 'Shipped')
        {
            $update = Orders::where('id',$id)->update(['status' => 'Shipped']);
            $_message = array('status' => 'true', 'message' => 'Order Status updated Successfully.');
        }
        if($type == 'Cancelled')
        {
            $update = Orders::where('id',$id)->update(['status' => 'Cancelled']);
            $_message = array('status' => 'true', 'message' => 'Order Status updated Successfully.');
        }
        
        if($update == TRUE)
        {
            $response = $_message;
        }
        else
        {
            $response = array('status' => 'false', 'message' => 'Something went wrong');
        }
        

        echo json_encode($response);
    }
}
