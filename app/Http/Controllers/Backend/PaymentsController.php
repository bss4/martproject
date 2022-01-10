<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Payments;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class PaymentsController extends Controller
{
    
    public $model   =   'Payments';
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
     * Show Paymentslist.
     *
     */
    public function listPayments()
    {
        return  View::make("admin.$this->model.index");
       
    }
    /**
        * Function for display page  for add new Payments page 
        *
        * @param null
        *
        * @return view page. 
    */

     /*Get All Payments As A JSON Data*/
    public function paymentsJsonListData()
    {
        $data = Payments::with('users','sellers')->get();

         return response()->json($data);
    } 
    /*End Get All Payments As A JSON Data*/

    public function addPayments(){
       
        return  View::make("admin.$this->model.add");
    } //end addAdvertisement()

    public function savePayments()
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
                'email'              => 'required|email|unique:Payments,email',
                'password'           => 'required',
                'confirm_password'   => 'required|same:password',
            ),$messages
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            


            /*$obj                    = new Payments;
            $obj->name              = $request['firstname'];
            $obj->email             = $request['email'];
            $obj->phone             = $request['phone'];
            $obj->first_name        = $request['firstname'];
            $obj->last_name         = $request['lastname'];
            $obj->password          = Hash::make($request['password']);
            $obj->save();*/

            $settingsEmail = "guptamanish3091995@gmail.com";
            $email = "blessy.bss4@gmail.com";
       
            $Paymentsname = "demo";
            $full_name = "demo".' '."Payments";
            
            $subject = "Payments register successfully latest.";
            $messageBody = "Hello ".$Paymentsname."<br><br>".$full_name." has been register successfully in tutorial points.";
            $this->sendMail($email, $full_name, $subject, $messageBody, $settingsEmail);
             
        }     

        Session::flash('success',  trans("Payments has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete Payments*/
    public function deletePayments($Id=0)
    {
        $obj    =  Payments::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("Payments has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete Payments*/

    public function editPayments($Id = 0)
    {
        $modeldata    =  Payments::find($Id);
        return  View::make("admin.$this->model.edit",compact('modeldata'));
    }

    public function viewPayments($Id = 0)
    {
      
        $modeldata    =  Payments::with('users','sellers')->find($Id);
        return  View::make("admin.$this->model.view",compact('modeldata'));

    }
}
