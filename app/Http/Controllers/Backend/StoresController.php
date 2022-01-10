<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Stores;
use App\Themes;
use App\Sellers;
use App\Shopworkinghours;
use App\StoreType;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class StoresController extends Controller
{
    
    public $model   =   'Stores';
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
     * Show Storeslist.
     *
     */
    public function listStores()
    {
        return  View::make("admin.$this->model.index");
    }
    /**
        * Function for display page  for add new Stores page 
        *
        * @param null
        *
        * @return view page. 
    */

    /*Get All Stores As A JSON Data*/
    public function storesJsonListData()
    {
        $data = Stores::orderBy('id','desc')->get();

        return response()->json($data);
    } 
    /*End Get All Stores As A JSON Data*/

    public function addStores(){
        
       $store_type =  StoreType::orderBy('id','desc')->get();
       $sellers =  Sellers::orderBy('id','desc')->get();
       $themes =  Themes::orderBy('id','desc')->get();
        return  View::make("admin.$this->model.add",compact('store_type','sellers','themes'));
    } //end addAdvertisement()

    public function saveStores()
    {
        $request = Request::all();
       
        $messages = array(
                    'business_name.required'  =>  "Business name field required.",
                    'name.required'           =>  "Store name field required.",
                    'state.required'          =>  "State field required.",
                    'country.required'        =>  "Country field required.",
                    'app_id.required'         =>  "Shop Id field required.",
                    'app_id.unique'         =>  "Shop Id already exist.",
                    'theme.required'          =>  "Theme field required.",
            );

    	$validator  =   Validator::make(
            Request::all(),
            array(
                'business_name'    => 'required|unique:stores',
                'name'             => 'required',
                'state'            => 'required',
                'country'          => 'required',
                'app_id'           => 'required|unique:sellers',
                'theme'            => 'required',
            ),$messages
        );
        if ($validator->fails())
        {   

            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
                
            
            if(!empty($request['seller_id']))
            {
                Sellers::where('id',$request['seller_id'])->update(['app_id'=>$request['app_id'],'theme'=>$request['theme']]);
            }    

            $obj = new Stores;

            if(Request::hasFile('logo')){

                    $extension  =    Request::file('logo')->getClientOriginalExtension();
                    $fileName   =   time().'-aadhar.'.$extension;
                    if(Request::file('logo')->move(STORE_LOGO_ROOT_PATH, $fileName)){

                        $obj->logo                =  $fileName;
                    }
              }
           
            $obj->seller_id              = $request['seller_id'];
            $obj->business_name          = $request['business_name'];
            $obj->name                   = $request['name'];
            $obj->city                   = $request['city'];
            $obj->state                  = $request['state'];
            $obj->store_type             = $request['store_type_id'];
            $obj->country                = $request['country'];
            $obj->pin_code               = $request['pin_code'];
            $obj->privacy_policy         = $request['privacy_policy'];
            $obj->return_refund_policy   = $request['return_refund_policy'];
            $obj->shipping_policy        = $request['shipping_policy'];
            $obj->terms_conditions       = $request['terms_conditions'];
            $obj->payments_policy        = $request['payments_policy'];
            $obj->about_us               = $request['about_us'];
            $obj->save();

            $last_id = $obj->id;

            for($i=1;$i<=7;$i++)
            {
                
                if($request['start_time'][$i]!='')
                {
                   $starttime_arr =  explode(':',$request['start_time'][$i]);
                   $stat = $starttime_arr[0].':'.$starttime_arr[1];

                }else
                {
                    $stat = 'Closed';
                }

                if($request['end_time'][$i]!='')
                {
                    
                   $endttime_arr =  explode(':',$request['end_time'][$i]);
                   $endtime = $endttime_arr[0].':'.$endttime_arr[1];
                  
                }else
                {
                    $endtime = 'Closed';
                }

                $hours_obj = new Shopworkinghours;
                $hours_obj->store_id = $last_id;
                $hours_obj->week_id = $i;
                $hours_obj->start_time = isset($stat)?$stat:'';
                $hours_obj->close_time = isset($endtime)?$endtime:'';
                $hours_obj->save();
                
               
            }
        }     

        Session::flash('success',  trans("Stores has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete Stores*/
    public function deleteStores($Id=0)
    {
        $obj    =  Stores::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("Stores has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete Stores*/

    public function editStores($Id = 0)
    {
        $modeldata    =  Stores::with('shopworkingtime','seller')->find($Id);

        $store_type =  StoreType::orderBy('id','desc')->get();
        $sellers =  Sellers::orderBy('id','desc')->get();
        $themes =  Themes::orderBy('id','desc')->get();
        return  View::make("admin.$this->model.edit",compact('modeldata','store_type','sellers','themes'));
    }

    public function viewStores($Id = 0)
    {
        $modeldata    =  Stores::with('shopworkingtime')->find($Id);
         return  View::make("admin.$this->model.view",compact('modeldata'));

    }

    public function updateStores($Id = 0)
    {
        
       $request = Request::all();
       
        $messages = array(
                    'business_name.required'  =>  "Business name field required.",
                    'business_name.unique'    =>  "Business name already exist.",
                    'name.required'           =>  "Store name field required.",
                    'state.required'          =>  "State field required.",
                    'country.required'        =>  "Country field required.",
                    'app_id.required'         =>  "Shop Id field required.",
                    'app_id.unique'           =>  "Shop Id already exist.",
                    'theme.required'          =>  "Theme field required.",
            );

        $validator  =   Validator::make(
            Request::all(),
            array(
               
                'business_name'    => 'required|unique:stores,business_name,'.$Id,',id',
                'name'             => 'required',
                'state'            => 'required',
                'country'          => 'required',
                'app_id'           => 'required|unique:sellers,app_id,'.$request['seller_id'].',id',
                'theme'            => 'required',
            ),$messages
        );
        if ($validator->fails())
        {   

            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
                
               
            if(!empty($request['seller_id']))
            {
                Sellers::where('id',$request['seller_id'])->update(['app_id'=>$request['app_id'],'theme'=>$request['theme']]);
            } 
            $obj = Stores::FindOrFail($Id);

            if(Request::hasFile('logo')){

                    $extension  =    Request::file('logo')->getClientOriginalExtension();
                    $fileName   =   time().'-aadhar.'.$extension;
                    if(Request::file('logo')->move(STORE_LOGO_ROOT_PATH, $fileName)){

                        $obj->logo                =  $fileName;
                    }
              }
           
            $obj->seller_id     = $request['seller_id'];
            $obj->business_name = $request['business_name'];
            $obj->name          = $request['name'];
            $obj->city          = $request['city'];
            $obj->state         = $request['state'];
            $obj->store_type    = $request['store_type_id'];
            $obj->country       = $request['country'];
            $obj->pin_code      = $request['pin_code'];
            $obj->privacy_policy         = $request['privacy_policy'];
            $obj->return_refund_policy   = $request['return_refund_policy'];
            $obj->shipping_policy        = $request['shipping_policy'];
            $obj->terms_conditions       = $request['terms_conditions'];
            $obj->payments_policy        = $request['payments_policy'];
            $obj->about_us               = $request['about_us'];
            $obj->save();


            for($i=1;$i<=7;$i++)
            {
                
                if($request['start_time'][$i]!='')
                {
                   $starttime_arr =  explode(':',$request['start_time'][$i]);
                   $stat = $starttime_arr[0].':'.$starttime_arr[1];

                }else
                {
                    $stat = 'Closed';
                }

                if($request['end_time'][$i]!='')
                {
                    
                   $endttime_arr =  explode(':',$request['end_time'][$i]);
                   $endtime = $endttime_arr[0].':'.$endttime_arr[1];
                  
                }else
                {
                    $endtime = 'Closed';
                }

                $hours_obj = Shopworkinghours::where('store_id',$Id)->where('week_id',$i)->update(['start_time'=>isset($stat)?$stat:'','close_time'=>isset($endtime)?$endtime:'']);
               
            }
             
        }     

        Session::flash('success',  trans("Stores has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }

    public function ckeditorimage()
    {
        if(Request::hasFile('upload')){

                $extension  =    Request::file('upload')->getClientOriginalExtension();
                $fileName   =   time().rand().'.'.$extension;
                if(Request::file('upload')->move(CKEDITOR_IMAGE_ROOT_PATH, $fileName)){
                    $function_number = $_GET['CKEditorFuncNum'];
                      $url = CKEDITOR_IMAGE_URL.$new_image_name;
                      $message = '';
                      echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($function_number, '$url', '$message');</script>";
                }
          }
    }
}
