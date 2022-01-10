<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController as BaseController;
use App\Sellers;
use App\User;
use App\Newsletter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Mail\Mailer;
use App\Http\Requests;
use Illuminate\Support\Facades\Hash;
use Auth,Blade,Config,Cache,Cookie,DB,File,Mail,mongoDate,Redirect,Response,Session,URL,View,Validator,hasFile;

class NewsletterController extends BaseController
{
    
    public $model   =   'Newsletter';
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
    public function newsletter($shopid,Request $request)
    {

        $selletdetails =  $this->shopaccess($shopid);
      
        $messages = array(

                'email.required'   =>  trans('messages.email.REQUIRED_ERROR')
                    
            );

        $validator  =   Validator::make(

            $request->all(),
            array(

                'email'   => 'required|email|unique:newsletter,email'
                
            ),$messages
        );

        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{

            $obj = new Newsletter;
            $obj->seller_id = $selletdetails->id;
            $obj->email = $request->email;
            $obj->save();
            
            $usersdetails = User::where('id',1)->first();
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            
            $headers .= 'From: <'.$usersdetails->email.'>' . "\r\n";
            $headers .= 'Cc:'.$usersdetails->email. "\r\n";
            $subject = "New Request For Newsletter Subscription.";
            $message = $request->email. " has been request for newsletter subscription.";
            $mail =  mail($usersdetails->email,$subject,$message,$headers);
        }
        
        session()->flash('success', 'Newsletter subscription successfully!');
        return Redirect::back();
        
    }
}
