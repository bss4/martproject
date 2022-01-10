<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Products;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class ProductsController extends Controller
{
    
    public $model   =   'Products';
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
     * Show Productslist.
     *
     */
    public function listProducts()
    {
        return  View::make("admin.$this->model.index");
       
    }
    /**
        * Function for display page  for add new Products page 
        *
        * @param null
        *
        * @return view page. 
    */
    public function addProducts(){
      
        return  View::make("admin.$this->model.add");
    } //end 

    public function saveProducts()
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
                'email'              => 'required|email|unique:Products,email',
                'password'           => 'required',
                'confirm_password'   => 'required|same:password',
            ),$messages
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
           
            $settingsEmail = "guptamanish3091995@gmail.com";
            $email = "blessy.bss4@gmail.com";
       
            $Productsname = "demo";
            $full_name = "demo".' '."Products";
            
            $subject = "Products register successfully latest.";
            $messageBody = "Hello ".$Productsname."<br><br>".$full_name." has been register successfully in tutorial points.";
            $this->sendMail($email, $full_name, $subject, $messageBody, $settingsEmail);
             
        }     

        Session::flash('success',  trans("Products has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete Products*/
    public function deleteProducts($Id=0)
    {
        $obj    =  Products::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("Products has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete Products*/

    public function editProducts($Id = 0)
    {
        $modeldata    =  Products::find($Id);
        return  View::make("admin.$this->model.edit",compact('modeldata'));
    }

    public function viewProducts($Id = 0)
    {
        $modeldata    =  Products::find($Id);
        return  View::make("admin.$this->model.view",compact('modeldata'));

    }

    public function updateProducts($Id = 0)
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
                'email'              => 'required|email|unique:Products,email',
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
                'email'              => 'required|email|unique:Products,email,'.$Id,
            ),$messages
            );

        }
        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            

            $obj                    = Products::findOrfail($Id);
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
        Session::flash('success',  trans("Products has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }

}
