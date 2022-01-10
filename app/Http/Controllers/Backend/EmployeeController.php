<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\User;
use App\Roles;
use App\Qualification;
use Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class EmployeeController extends Controller
{
    
    public $model   =   'Employee';
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
    public function listEmployee()
    {
        $modeldata = User::orderBy('id','DESC')->get();
        return  View::make("admin.$this->model.index",compact('modeldata'));
    }
    /**
        * Function for display page  for add new user page 
        *
        * @param null
        *
        * @return view page. 
    */
    public function addEmployee(){
        $roles = Roles::pluck('name','id')->toArray();
        $qualification = Qualification::pluck('name','id')->toArray();
        
        return  View::make("admin.$this->model.add",compact('roles','qualification'));
    } //end addAdvertisement()

    public function saveEmployee()
    {
        $request = Request::all();

        $messages = array(
                    'firstname.required'          =>  trans('messages.firstname.REQUIRED_ERROR'),
                    'lastname.required'           =>  trans('messages.lastname.REQUIRED_ERROR'),
                    'status.required'             =>  trans('messages.status.REQUIRED_ERROR'),
                    'role.required'               =>  trans('messages.role.REQUIRED_ERROR'),
                    'gender.required'             =>  trans('messages.gender.REQUIRED_ERROR'),
                    'dob.required'                =>  trans('messages.dob.REQUIRED_ERROR'),
                    'dateofjoinig.required'       =>  trans('messages.dateofjoinig.REQUIRED_ERROR'),
                    'phone.required'              =>  trans('messages.phone.REQUIRED_ERROR'),
                    'qualification.required'      =>  trans('messages.qualification.REQUIRED_ERROR'),
                    'emergencynumber.required'    =>  trans('messages.emergencynumber.REQUIRED_ERROR'),
                    'fathername.required'         =>  trans('messages.fathername.REQUIRED_ERROR'),
                    'current_address.required'    =>  trans('messages.current_address.REQUIRED_ERROR'),
                    'permanent_address.required'  =>  trans('messages.permanent_address.REQUIRED_ERROR'),
                    'bank_account_number'         =>  trans('messages.bank_account_number.REQUIRED_ERROR'),
                    'bank_name'                   =>  trans('messages.bank_name.REQUIRED_ERROR'),
                    'ifsc_code'                   =>  trans('messages.ifsc_code.REQUIRED_ERROR'),
                    'pf_account_number'           =>  trans('messages.pf_account_number.REQUIRED_ERROR'),
                    'un_number'                   =>  trans('messages.un_number.REQUIRED_ERROR'),
                    'pf_status'                   =>  trans('messages.pf_status.REQUIRED_ERROR'),
                    'salary'                      =>  trans('messages.salary.REQUIRED_ERROR'),
                    'pan_number'                  =>  trans('messages.pan_number.REQUIRED_ERROR'),
            );

    	$validator  =   Validator::make(
            Request::all(),
            array(
                'firstname'          => 'required',
                'lastname'           => 'required',
                'status'             => 'required',
                'role'               => 'required',
                'gender'             => 'required',
                'dob'                => 'required',
                'dateofjoinig'       => 'required',
                'phone'              => 'required|min:10',
                'qualification'      => 'required',
                'emergencynumber'    => 'required',
                'fathername'         => 'required',
                'current_address'    => 'required',
                'permanent_address'  => 'required',
                'bank_account_number'=> 'required',
                'bank_name'          => 'required',
                'ifsc_code'          => 'required',
                'pf_account_number'  => 'required',
                'un_number'          => 'required',
                'pf_status'          => 'required',
                'salary'             => 'required',
                'pan_number'         => 'required',
            ),$messages
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            if($request['photo']) {
                $image = $request['photo'];
                $name = time().'.'.$image->getClientOriginalExtension();
                $image->move(EMPLOYEE_IMAGE_ROOT_PATH, $name);
            }

            $obj                    = new User;
            $obj->name              = $request['firstname'];
            $obj->email             = $request['email'];
            $obj->photo             = $name;
            $obj->first_name        = $request['firstname'];
            $obj->last_name         = $request['lastname'];
            $obj->status            = ACTIVE;
            $obj->gender            = $request['gender'];
            $obj->date_of_birth     = $request['dob'];
            $obj->date_of_joining   = $request['dateofjoinig'];
            $obj->phone             = $request['phone'];
            $obj->role              = $request['role'];
            $obj->qualification     = $request['qualification'];
            $obj->emergency_number  = $request['emergencynumber'];
            $obj->pan_number        = $request['pan_number'];
            $obj->father_name       = $request['fathername'];
            $obj->current_address   = $request['current_address'];
            $obj->permanent_address = $request['permanent_address'];
            $obj->salary            = $request['salary'];
            $obj->account_number    = $request['bank_account_number'];
            $obj->bank_name         = $request['bank_name'];
            $obj->ifsc_code         = $request['ifsc_code'];
            $obj->pf_account_number = $request['pf_account_number'];
            $obj->un_number         = $request['un_number'];
            $obj->pf_status         = $request['pf_status'];
            $obj->save();
             
        }     

        Session::flash('success',  trans("Employee has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete user*/
    public function deleteEmployee($Id=0)
    {
        $obj    =  User::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("Employee has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete user*/

    public function editEmployee($Id = 0)
    {
        $roles = Roles::pluck('name','id')->toArray();
        $qualification = Qualification::pluck('name','id')->toArray();
        $modeldata    =  User::find($Id);
        return  View::make("admin.$this->model.edit",compact('modeldata','roles','qualification'));
    }

    public function updateEmployee($Id = 0)
    {
        
        $request = Request::all();
        $messages = array(
                    'firstname.required'          =>  trans('messages.firstname.REQUIRED_ERROR'),
                    'lastname.required'           =>  trans('messages.lastname.REQUIRED_ERROR'),
                    'status.required'             =>  trans('messages.status.REQUIRED_ERROR'),
                    'role.required'               =>  trans('messages.role.REQUIRED_ERROR'),
                    'gender.required'             =>  trans('messages.gender.REQUIRED_ERROR'),
                    'dob.required'                =>  trans('messages.dob.REQUIRED_ERROR'),
                    'dateofjoinig.required'       =>  trans('messages.dateofjoinig.REQUIRED_ERROR'),
                    'phone.required'              =>  trans('messages.phone.REQUIRED_ERROR'),
                    'qualification.required'      =>  trans('messages.qualification.REQUIRED_ERROR'),
                    'emergencynumber.required'    =>  trans('messages.emergencynumber.REQUIRED_ERROR'),
                    'fathername.required'         =>  trans('messages.fathername.REQUIRED_ERROR'),
                    'current_address.required'    =>  trans('messages.current_address.REQUIRED_ERROR'),
                    'permanent_address.required'  =>  trans('messages.permanent_address.REQUIRED_ERROR'),
                    'bank_account_number'         =>  trans('messages.bank_account_number.REQUIRED_ERROR'),
                    'bank_name'                   =>  trans('messages.bank_name.REQUIRED_ERROR'),
                    'ifsc_code'                   =>  trans('messages.ifsc_code.REQUIRED_ERROR'),
                    'pf_account_number'           =>  trans('messages.pf_account_number.REQUIRED_ERROR'),
                    'un_number'                   =>  trans('messages.un_number.REQUIRED_ERROR'),
                    'pf_status'                   =>  trans('messages.pf_status.REQUIRED_ERROR'),
                    'salary'                      =>  trans('messages.salary.REQUIRED_ERROR'),
                    'pan_number'                  =>  trans('messages.pan_number.REQUIRED_ERROR'),
            );

        $validator  =   Validator::make(
            Request::all(),
            array(
                'firstname'          => 'required',
                'lastname'           => 'required',
                'status'             => 'required',
                'role'               => 'required',
                'gender'             => 'required',
                'dob'                => 'required',
                'dateofjoinig'       => 'required',
                'phone'              => 'required',
                'qualification'      => 'required',
                'emergencynumber'    => 'required',
                'fathername'         => 'required',
                'current_address'    => 'required',
                'permanent_address'  => 'required',
                'bank_account_number'=> 'required',
                'bank_name'          => 'required',
                'ifsc_code'          => 'required',
                'pf_account_number'  => 'required',
                'un_number'          => 'required',
                'pf_status'          => 'required',
                'salary'             => 'required',
                'pan_number'         => 'required',
            ),$messages
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

            $obj                    = User::findOrfail($Id);
            
            if($request['photo']) {
                $image = $request['photo'];
                $name = time().'.'.$image->getClientOriginalExtension();
                $image->move(EMPLOYEE_IMAGE_ROOT_PATH, $name);
            	$obj->photo             = $name;
            
            }

            $obj->name              = $request['firstname'];
            $obj->email             = $request['email'];
            $obj->first_name        = $request['firstname'];
            $obj->last_name         = $request['lastname'];
            $obj->status            = ACTIVE;
            $obj->gender            = $request['gender'];
            $obj->date_of_birth     = $request['dob'];
            $obj->date_of_joining   = $request['dateofjoinig'];
            $obj->phone             = $request['phone'];
            $obj->role              = $request['role'];
            $obj->qualification     = $request['qualification'];
            $obj->emergency_number  = $request['emergencynumber'];
            $obj->pan_number        = $request['pan_number'];
            $obj->father_name       = $request['fathername'];
            $obj->current_address   = $request['current_address'];
            $obj->permanent_address = $request['permanent_address'];
            $obj->salary            = $request['salary'];
            $obj->account_number    = $request['bank_account_number'];
            $obj->bank_name         = $request['bank_name'];
            $obj->ifsc_code         = $request['ifsc_code'];
            $obj->pf_account_number = $request['pf_account_number'];
            $obj->un_number         = $request['un_number'];
            $obj->pf_status         = $request['pf_status'];
            $obj->save();
             
        } 
        Session::flash('success',  trans("Employee has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }
}
