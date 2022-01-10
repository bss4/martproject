<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController as BaseController;
use App\Sellers;
use App\User;
use App\Cart;
use App\Contactus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Mail\Mailer;
use App\Http\Requests;
use Illuminate\Support\Facades\Hash;
use Auth,Blade,Config,Cache,Cookie,DB,File,Mail,mongoDate,Redirect,Response,Session,URL,View,Validator,hasFile;

class ContactController extends BaseController
{
    
    public $model   =   'Contact';
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
     * Show Contactlist.
     *
     */
    public function index($shopid)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        
        return view('frontend.'.$sellerdetails->theme.'.contact',compact('shopid'));
    }

    public function contactMail($shopid,Request $request)
    {
        $requestdata = $request->all();

        $selletdetails =  $this->shopaccess($shopid);
        $validator  =   Validator::make(
            $request->all(),
            array(
                'name'               => 'required',
                'email'              => 'required|email',
                'phone'              => 'required|numeric|digits:10',
                'message'            => 'required'
            )
        );
        if ($validator->fails())
        {   
           
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            $sellerdetails =  $this->shopaccess($shopid);

            $obj            = new Contactus;
            $obj->seller_id = $sellerdetails->id;
            $obj->name      = $requestdata['name'];
            $obj->email     = $requestdata['email'];
            $obj->phone     = $requestdata['phone'];
            $obj->message   = $requestdata['message'];
            $obj->save();
            

           $usersdetails = User::where('id',1)->first();
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            
            $headers .= 'From: <'.$usersdetails->email.'>' . "\r\n";
            $headers .= 'Cc:'.$usersdetails->email. "\r\n";
            $subject = "Thank you for contacting us. We will respond to you as soon as possible.";
            $mail =  mail($usersdetails->email,$subject,$request->message,$headers);
            
        
        }  
        Session::flash('success',  trans("form successfully submitted."));  
        return Redirect::back();
    }
}
