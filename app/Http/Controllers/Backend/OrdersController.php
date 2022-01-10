<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Orders;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class OrdersController extends Controller
{
    
    public $model   =   'Orders';
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
     * Show Orderslist.
     *
     */
    public function listOrders()
    {
        
        
        return  View::make("admin.$this->model.index");
   
    }

    /*Get All Orders As A JSON Data*/
    public function ordersJsonListData()
    {
        $data = Orders::with('sellers','users')->get();
        return response()->json($data);
    } 
    /*End Get All Orders As A JSON Data*/


    /**
        * Function for display page  for add new Orders page 
        *
        * @param null
        *
        * @return view page. 
    */
    public function addOrders(){
      
        return  View::make("admin.$this->model.add",compact('breadcrumbs'));
    } //end addAdvertisement()


    public function saveOrders()
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
                'email'              => 'required|email|unique:Orders,email',
                'password'           => 'required',
                'confirm_password'   => 'required|same:password',
            ),$messages
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            


            /*$obj                    = new Orders;
            $obj->name              = $request['firstname'];
            $obj->email             = $request['email'];
            $obj->phone             = $request['phone'];
            $obj->first_name        = $request['firstname'];
            $obj->last_name         = $request['lastname'];
            $obj->password          = Hash::make($request['password']);
            $obj->save();*/

            $settingsEmail = "guptamanish3091995@gmail.com";
            $email = "blessy.bss4@gmail.com";
       
            $Ordersname = "demo";
            $full_name = "demo".' '."Orders";
            
            $subject = "Orders register successfully latest.";
            $messageBody = "Hello ".$Ordersname."<br><br>".$full_name." has been register successfully in tutorial points.";
            $this->sendMail($email, $full_name, $subject, $messageBody, $settingsEmail);
             
        }     

        Session::flash('success',  trans("Orders has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete Orders*/
    public function deleteOrders($Id=0)
    {
        $obj    =  Orders::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("Orders has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete Orders*/

    public function editOrders($Id = 0)
    {
        $modeldata    =  Orders::find($Id);
        return  View::make("admin.$this->model.edit",compact('modeldata'));
    }

    public function viewOrders($id = 0)
    {
       
        $modeldata = Orders::with('sellers','users')->find($id);
        
        return  View::make("admin.$this->model.view",compact('modeldata'));

    }

    public function updateOrders($Id = 0)
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
                'email'              => 'required|email|unique:Orders,email',
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
                'email'              => 'required|email|unique:Orders,email,'.$Id,
            ),$messages
            );

        }
        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

            $obj                    = Orders::findOrfail($Id);
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
        Session::flash('success',  trans("Orders has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }

}
