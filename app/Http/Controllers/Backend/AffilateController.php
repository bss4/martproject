<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\User;
use App\Plans;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class AffilateController extends Controller
{
    
    public $model   =   'User';
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
     * Show Affilatelist.
     *
     */
    public function listAffilate()
    {
        
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route('Affilate.index'));
        ### breadcrumbs End ###
        $conditionArray=array();
        $formData=Request::input();
        $searchVariable =   array(); 
        if ((Request::all() && isset($formData['display'])) || isset($formData['page']) ) {
            $searchData =   Request::all();
            
            unset($searchData['display']);
            unset($searchData['_token']);
            unset($searchData['sortBy']);
            unset($searchData['order']);
            
            if(isset($searchData['page'])){
                unset($searchData['page']);
            }
            
            if(isset($searchData['records_per_page'])){
                unset($searchData['records_per_page']);
            }
            foreach($searchData as $fieldName => $fieldValue){
                $fieldValue =   trim($fieldValue);
                if($fieldValue != ''){
                    if($fieldName == 'active' ){
                        $conditionArray[] =  ['is_active',(int)$fieldValue];
                    }
                    else{
                        $conditionArray[] =  [$fieldName, 'LIKE', '%'.$fieldValue.'%'];
                    }
                }
                $searchVariable =   array_merge($searchVariable,array($fieldName => $fieldValue));
            }
        }
        
        if(Request::get('records_per_page')!=''){
            $searchVariable =   array_merge($searchVariable,array('records_per_page' => Request::get('records_per_page')));
        }
        $sortBy = (Request::input('sortBy')) ? Request::input('sortBy') : 'created_at';
        $order  = (Request::input('order')) ? Request::input('order')   : 'DESC';
        $limit          =   (Request::get('records_per_page')!='') ? Request::get('records_per_page'):RECORDS_PER_PAGE; 

        $DB =   User::query();
        if(isset($conditionArray) && !empty($conditionArray)){
            $DB->where($conditionArray);
        }
        // $DB->where('role','!=',ADMIN_ROLE);
        $DB->orderBy($sortBy,$order);

        //$modeldata     =   $DB->paginate((int)$limit);
        $modeldata = User::where('role',AFFILATE_ROLE)->orderBy('id','DESC')->get();
        return  View::make("admin.Affilate.index", compact('modeldata'));
       /* $modeldata = Affilate::where('role','!=',ADMIN_ROLE)->orderBy('id','DESC')->get();
        return  View::make("admin.$this->model.index",compact('modeldata'));*/
    }
    /*Get All Affilate As A JSON Data*/
    public function affilateJsonData()
    {
        $data = User::select('id','email', 'name', 'affiliate_id')->where('role',AFFILATE_ROLE)->orderBy('id','desc')->get();
        return response()->json($data);
    } 
    /*End Get All Affilate As A JSON Data*/

    /*Get Affilate Seller As A JSON Data*/
    public function affilateSellerJson($id=0)
    {
        
        $data = User::with('affiliateseller')->where('id', $id)->first();
        $plans = Plans::get()->pluck('name', 'id');
        
        return View::make("admin.Affilate.view", compact('data','plans'));
    } 
    /*End Get Affilate Seller As A JSON Data*/

    /**
        * Function for display page  for add new Affilate page 
        *
        * @param null
        *
        * @return view page. 
    */
    public function addAffilate(){
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route('Affilate.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_add"),'');
        return  View::make("admin.Affilate.add",compact('breadcrumbs'));
    } //end addAdvertisement()

    /*Random String function*/

    private function random_string($length = 8)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+/*|\-.';

        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }

    public function saveAffilate()
    {

        $request = Request::all();
        
        
        $validator  =   Validator::make(
            Request::all(),
            array(
                'email'              => 'required|unique:users',
                'name'               => 'required'
            )
        );
       
        if ($validator->fails())
        {   
            
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            
            $obj                    = new User;
            $obj->name              = $request['name'];
            $obj->email             = $request['email'];
            $obj->password          = Hash::make(123456);
            $obj->role              = AFFILATE_ROLE;
            $obj->affiliate_id      = isset($request['app_id'])?$request['app_id']:'';
            $obj->save();
        }     

        Session::flash('success',  trans("Affilate has been added successfully."));  
        return Redirect::route("Affilate.index");
        
    }

    /*delete Affilate*/
    public function deleteAffilate($Id=0)
    {
        $obj        =  User::find($Id);
        $seller     =  User::where('id',$Id)->first();

        if($obj->delete())
        {
           
           Session::flash('success', trans("Affilate has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("Affilate.index");

    }
    /*end delete Affilate*/


    /*delete Affilate*/
    public function multipleDeleteAffilate()
    {
        $request = Request::all();
        print_r($request);die;
        $response = DB::table("users")->whereIn('id',explode(",",$ids))->where('role',AFFILATE_ROLE)->delete();
        

        $seller     =  User::where('id',$Id)->first();
        if($obj->delete())
        {
           Session::flash('success', trans("Affilate has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }
        return Redirect::route("Affilate.index");
    }
    /*end delete Affilate*/


    public function editAffilate($Id = 0)
    {
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route('Affilate.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_edit"),'');

        $modeldata    =  User::find($Id);
        return  View::make("admin.Affilate.edit",compact('modeldata','breadcrumbs'));
    }

    public function viewAffilate($Id = 0)
    {
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_edit"),'');

        $modeldata    =  User::find($Id);

        $refferal_by  =  User::where("refferal_by",$modeldata->refferal_id)->get();

         return  View::make("admin.$this->model.view",compact('modeldata','breadcrumbs','refferal_by'));

    }

    public function updateAffilate($Id = 0)
    {
        
        $request = Request::all();
       
        $validator  =   Validator::make(
            Request::all(),
            array(
                'email'              => 'required|unique:users,email,'.$Id,
                'name'               => 'required'
            )
        );
        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            $appID                  = User::where('id',$Id)->first();
            $obj                    = User::findOrfail($Id);
            $obj->name              = $request['name'];
            $obj->email             = $request['email'];
            $obj->affiliate_id      = isset($request['app_id'])?$request['app_id']:$appID->app_id;
            $obj->save();
             
        } 
        Session::flash('success',  trans("Affilate has been updated successfully."));  
        return Redirect::route("Affilate.index");
    }

}
