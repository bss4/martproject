<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Reviews;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class ReviewsController extends Controller
{
    
    public $model   =   'Reviews';
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
     * Show Reviewslist.
     *
     */
    public function listReviews()
    {
        
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
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

        $DB =   Reviews::query();
        if(isset($conditionArray) && !empty($conditionArray)){
            $DB->where($conditionArray);
        }
        $DB->where('role','!=',ADMIN_ROLE);
        $DB->orderBy($sortBy,$order);

        $modeldata     =   $DB->paginate((int)$limit);
        
        return  View::make("admin.$this->model.index",compact('limit','breadcrumbs','modeldata','searchVariable','sortBy','order'));
       /* $modeldata = Reviews::where('role','!=',ADMIN_ROLE)->orderBy('id','DESC')->get();
        return  View::make("admin.$this->model.index",compact('modeldata'));*/
    }
    /**
        * Function for display page  for add new Reviews page 
        *
        * @param null
        *
        * @return view page. 
    */
    public function addReviews(){
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_add"),'');
        return  View::make("admin.$this->model.add",compact('breadcrumbs'));
    } //end addAdvertisement()

    public function saveReviews()
    {
        $request = Request::all();

        $messages = array(
                    'firstname.required'          =>  trans('messages.firstname.REQUIRED_ERROR'),
                    'lastname.required'           =>  trans('messages.lastname.REQUIRED_ERROR'),
                    'phone.required'              =>  trans('messages.phone.REQUIRED_ERROR'),
                    'email.required'              =>  trans('messages.email.REQUIRED_ERROR'),
                    'password.required'           =>  trans('messages.password.REQUIRED_ERROR'),
            );

    	$validator  =   Validator::make(
            Request::all(),
            array(
                'firstname'          => 'required',
                'lastname'           => 'required',
                'phone'              => 'required|numeric|min:10',
                'email'              => 'required|email|unique:Reviews,email',
                'password'           => 'required',
                'confirm_password'   => 'required|same:password',
            ),$messages
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            


            /*$obj                    = new Reviews;
            $obj->name              = $request['firstname'];
            $obj->email             = $request['email'];
            $obj->phone             = $request['phone'];
            $obj->first_name        = $request['firstname'];
            $obj->last_name         = $request['lastname'];
            $obj->password          = Hash::make($request['password']);
            $obj->save();*/

            $settingsEmail = "guptamanish3091995@gmail.com";
            $email = "blessy.bss4@gmail.com";
       
            $Reviewsname = "demo";
            $full_name = "demo".' '."Reviews";
            
            $subject = "Reviews register successfully latest.";
            $messageBody = "Hello ".$Reviewsname."<br><br>".$full_name." has been register successfully in tutorial points.";
            $this->sendMail($email, $full_name, $subject, $messageBody, $settingsEmail);
             
        }     

        Session::flash('success',  trans("Reviews has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete Reviews*/
    public function deleteReviews($Id=0)
    {
        $obj    =  Reviews::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("Reviews has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete Reviews*/

    public function editReviews($Id = 0)
    {
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_edit"),'');

        $modeldata    =  Reviews::find($Id);
        return  View::make("admin.$this->model.edit",compact('modeldata','breadcrumbs'));
    }

    public function viewReviews($Id = 0)
    {
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_edit"),'');

        $modeldata    =  Reviews::find($Id);
         return  View::make("admin.$this->model.view",compact('modeldata','breadcrumbs'));

    }

    public function updateReviews($Id = 0)
    {
        
        $request = Request::all();
        $messages = array(
                    'firstname.required'          =>  trans('messages.firstname.REQUIRED_ERROR'),
                    'lastname.required'           =>  trans('messages.lastname.REQUIRED_ERROR'),
                    'phone.required'              =>  trans('messages.phone.REQUIRED_ERROR'),
                    'email.required'              =>  trans('messages.email.REQUIRED_ERROR'),
                    'password.required'           =>  trans('messages.password.REQUIRED_ERROR'),
            );

        if($request['password'])
        {
            $validator  =   Validator::make(
            Request::all(),
            array(
                'firstname'          => 'required',
                'lastname'           => 'required',
                'phone'              => 'required|numeric|min:10',
                'email'              => 'required|email|unique:Reviews,email',
                'password'           => 'required',
                'confirm_password'   => 'required|same:password',
            ),$messages
        );

        }else
        {
            $validator  =   Validator::make(
            Request::all(),
            array(
                'firstname'          => 'required',
                'lastname'           => 'required',
                'phone'              => 'required|numeric|min:10',
                'email'              => 'required|email|unique:Reviews,email,'.$Id,
            ),$messages
            );

        }
        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

            $obj                    = Reviews::findOrfail($Id);
            if($request['password'])
            {
                $obj->password      = Hash::make($request['password']);    
            }
            $obj->name              = $request['firstname'];
            $obj->email             = $request['email'];
            $obj->first_name        = $request['firstname'];
            $obj->last_name         = $request['lastname'];
            $obj->phone             = $request['phone'];
            $obj->save();
             
        } 
        Session::flash('success',  trans("Reviews has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }

}
