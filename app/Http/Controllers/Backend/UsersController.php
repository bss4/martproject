<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\User;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class UsersController extends Controller
{
    
    public $model   =   'Users';
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
     * Show userlist.
     *
     */
    public function listUsers()
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

        $DB =   User::query();
        if(isset($conditionArray) && !empty($conditionArray)){
            $DB->where($conditionArray);
        }
        $DB->where('role','!=',ADMIN_ROLE);
        $DB->orderBy($sortBy,$order);

        $modeldata     =   $DB->paginate((int)$limit);
        
        return  View::make("admin.$this->model.index",compact('limit','breadcrumbs','modeldata','searchVariable','sortBy','order'));
       /* $modeldata = User::where('role','!=',ADMIN_ROLE)->orderBy('id','DESC')->get();
        return  View::make("admin.$this->model.index",compact('modeldata'));*/
    }
    /**
        * Function for display page  for add new user page 
        *
        * @param null
        *
        * @return view page. 
    */
    public function addUsers(){
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_add"),'');
        return  View::make("admin.$this->model.add",compact('breadcrumbs'));
    } //end addAdvertisement()

    public function saveUsers()
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
                'email'              => 'required|email|unique:users,email',
                'password'           => 'required',
                'confirm_password'   => 'required|same:password',
            ),$messages
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            


            /*$obj                    = new User;
            $obj->name              = $request['firstname'];
            $obj->email             = $request['email'];
            $obj->phone             = $request['phone'];
            $obj->first_name        = $request['firstname'];
            $obj->last_name         = $request['lastname'];
            $obj->password          = Hash::make($request['password']);
            $obj->save();*/

            $settingsEmail = "guptamanish3091995@gmail.com";
            $email = "blessy.bss4@gmail.com";
       
            $username = "demo";
            $full_name = "demo".' '."user";
            
            $subject = "User register successfully latest.";
            $messageBody = "Hello ".$username."<br><br>".$full_name." has been register successfully in tutorial points.";
            $this->sendMail($email, $full_name, $subject, $messageBody, $settingsEmail);
             
        }     

        Session::flash('success',  trans("Users has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete user*/
    public function deleteUsers($Id=0)
    {
        $obj    =  User::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("Users has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete user*/

    public function editUsers($Id = 0)
    {
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_edit"),'');

        $modeldata    =  User::find($Id);
        return  View::make("admin.$this->model.edit",compact('modeldata','breadcrumbs'));
    }

    public function viewUsers($Id = 0)
    {
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_edit"),'');

        $modeldata    =  User::find($Id);
         return  View::make("admin.$this->model.view",compact('modeldata','breadcrumbs'));

    }

    public function updateUsers($Id = 0)
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
                'email'              => 'required|email|unique:users,email',
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
                'email'              => 'required|email|unique:users,email,'.$Id,
            ),$messages
            );

        }
        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

            $obj                    = User::findOrfail($Id);
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
        Session::flash('success',  trans("Users has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }

    //Edit Admin Profile Section
    public function editAdminProfile($Id = 0)
    {
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_edit"),'');

        $modeldata    =  User::where('id',$Id)->first();
        
        return  View::make("admin.$this->model.admin",compact('modeldata','breadcrumbs'));
    }

    public function updateAdmin($Id = 0)
    {
        
        $request = Request::all();

        if($request['password'])
        {
            $validator  =   Validator::make(
            Request::all(),
            array(
                'name'               => 'required',
                'email'              => 'required|email',
                'confirm_password'   => 'required|same:password',
            )
        );

        }else
        {
            $validator  =   Validator::make(
            Request::all(),
            array(
                'name'               => 'required',
                'email'              => 'required|email',
            )
            );

        }
        
        if ($validator->fails())
        {   
            
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

            $obj                    = User::findOrfail($Id);
            if($request['password'])
            {
                $obj->password      = Hash::make($request['password']);    
            }
            if(Request::hasFile('image')){
                $file_path = ADMIN_LOGO_ROOT_PATH.$obj['image'];
                if(!empty($obj['image']))
                {
                   if(File::exists($file_path)){
                        unlink($file_path);
                    } 
                }

                $extension  =    Request::file('image')->getClientOriginalExtension();
                $fileName   =   time().'-admin.'.$extension;
                if(Request::file('image')->move(ADMIN_LOGO_ROOT_PATH, $fileName)){

                    $obj->image                =  $fileName;
                }
            }
            $obj->name              = $request['name'];
            $obj->email             = $request['email'];
            $obj->save();
             
        } 
        Session::flash('success',  trans("Admin has been updated successfully."));  
        return Redirect::route("admin.dashboard");
    }
    //End Admin Edit Section

    public static function sendMail($to, $toName, $subject, $messageBody, $from = '', $fromName = '', $files = false, $path='', $attachmentName='') {
        $data = array();
        $data['to'] = $to;
        $data['fullName'] = !empty($toName) ? $toName : '';
        $data['fromName'] = !empty($fromName) ? $fromName : 'Blessy';
        $data['from'] = !empty($from) ? $from : '';
        $data['subject'] = $subject;

        $data['filepath'] = $path;
        $data['attachmentName'] = $attachmentName;
        
        if($files===false){
          $mailsend=  Mail::send('emails.template', array('messageBody' => $messageBody), function($message) use ($data) {
                $message->to($data['to'], $data['fullName'])->from($data['from'], $data['fromName'])->subject($data['subject']);
            });

        }else{
            if($attachmentName!=''){
                Mail::send('emails.template', array('messageBody'=> $messageBody), function($message) use ($data){
                    $message->to($data['to'], $data['fullName'])->from($data['from'], $data['fromName'])->subject($data['subject'])->attach($data['filepath'],array('as'=>$data['attachmentName']));
                });
            }else {
                Mail::send('emails.template', array('messageBody'=> $messageBody), function($message) use ($data){
                    $message->to($data['to'], $data['fullName'])->from($data['from'], $data['fromName'])->subject($data['subject'])->attach($data['filepath']);
                });
            }
        }

        
    }//end sendMail()

    public function listUsersjson()
    {

        return  View::make("admin.$this->model.jsondatalist");
    }

    public function userJsonData()
    {
        $data = User::select('id','name','email')->get();

        $columns[] = ["headerName" => 'ID',
                      "field"=> 'id',
                      "width"=> 125,
                      "filter"=> true,
                      "checkboxSelection"=> true,
                      "headerCheckboxSelectionFilteredOnly"=> true,
                      "headerCheckboxSelection"=> true];

        $columns[] = ["headerName"=> 'Name',
              "field"=> 'name',
              "filter"=> true,
              "width"=> 175];

        $columns[] = ["headerName"=> 'Email',
              "field"=> 'email',
              "filter"=> true,
              "width"=> 225];

      
        return response()->json([
            'columns' => $columns,
            'results'    => $data
        ]);
       //return response()->json($data);
        //return  View::make("admin.$this->model.jsondatalist");
    }
}
