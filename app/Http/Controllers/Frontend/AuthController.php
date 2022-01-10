<?php

namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController as BaseController;
use App\User;
use App\Sellers;
use App\Catalogue;
use App\Products;
use App\Wishlist;
use App\Cart;
use App\Orders;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Mail\Mailer;
use App\Http\Requests;
use Illuminate\Support\Facades\Hash;
use Auth,Blade,Config,Cache,Cookie,DB,File,Mail,mongoDate,Redirect,Response,Session,URL,View,Validator,hasFile;

class AuthController extends BaseController
{

    public $model   =   'Home';
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
        View::share('modelName',$this->model);

        
    }

    public function index($shopid)
    {
        
        if($shopid=='admin')
        {

            if(Auth::check())
            {
                return redirect()->to('/admin/dashboard');
            }else
            {
                return redirect()->to('/admin/adminlogin');
            }
            
        }

        $sellerdetails =  $this->shopaccess($shopid);
       
        if($sellerdetails)
        {
          
            $catalogs_list = $this->_functionCatalogues($sellerdetails->id);
            
            $seller_products = Products::where('seller_id',$sellerdetails->id)->orderBy('id','desc')->get();

            
            if(session()->has('cart_session'))
            {
                $cart_session = session()->get('cart_session');
               
                $cart = Cart::with('product')->where('seller_id',$sellerdetails->id)->get()->toArray();
                
                session()->put('cart',$cart);
               
                
            }else
            {
                session()->forget('cart');
                session()->forget('cart_session');
               
            }
            
            if(empty($sellerdetails->theme))
            {
               return abort(404);
         
            }
            return view('frontend.'.$sellerdetails->theme.'.home',compact('shopid','catalogs_list','seller_products','sellerdetails'));

         }else
         {
            return abort(404);
         }
       
    }

    public function showLogin()
    {
       return view('admin.auth.login');
    }

    public function setsession($shopid)
    {
        $sellerdetails =  $this->shopaccess($shopid);
         $user_id = Auth::user()->id;
         //set wishlist into wishlist session
         $wishlist = Wishlist::where('user_id',$user_id)->where('seller_id',$sellerdetails->id)->get();
         if(!empty($wishlist) && count($wishlist)>0)
         {
            session()->put('wishlist',$wishlist);
         }
         

         $cart = Cart::with('product')->where('user_id',$user_id)->where('seller_id',$sellerdetails->id)->get()->toArray();
         if(!empty($cart) && count($cart)>0)
         {
            if(session()->has('cart_session'))
            {
                $cart_session = session()->get('cart_session');
                Cart::where('session_id',$cart_session)->update(['user_id'=>$user_id,'session_id'=>$cart[0]['session_id']]);

                $cart = Cart::with('product')->where('seller_id',$sellerdetails->id)->where('user_id',$user_id)->get()->toArray();
                session()->put('cart',$cart);
                session()->put('cart_session',$cart[0]['session_id']);
                
            }else
            {
                session()->put('cart',$cart);
                session()->put('cart_session',$cart[0]['session_id']);
            }
            
         }else
         {
            if(session()->has('cart_session'))
            {
                $cart_session = session()->get('cart_session');
                Cart::where('session_id',$cart_session)->where('seller_id',$sellerdetails->id)->update(['user_id'=>$user_id]);

                $cart = Cart::with('product')->where('seller_id',$sellerdetails->id)->where('user_id',$user_id)->get()->toArray();
                session()->put('cart',$cart);
            }
         }
         
    }
    public function doLogin($shopid,Request $request)
    {
       
            request()->validate([
                'email' => 'required',
                'password' => 'required',
                ]);
            
            $remember   =   (!empty($request->remember)) ? true : false; 
            $credentials = $request->only('email', 'password');
            
            $user = User::where('email',$request->email)->where('role',FRONT_USER_ROLE)->first();
            
            if (Auth::attempt($credentials)) {
                if($remember == true){  
                    $rememberData['email']          = $request->email;
                    $rememberData['password']       = bcrypt($request->password);
                    $rememberData['remember_me']    = $remember;
                    setcookie('remember_admin',json_encode($rememberData), time() + (86400 * 30)); 
                }else{
                    setcookie('remember_admin','', time() - (86400 - 30)    ); 
                }

                if(Auth::user()->role == FRONT_USER_ROLE)
                {
                    // Authentication passed...
                     $this->setsession($shopid);
                     //return Redirect::back();
                     //return view('frontend.'.$sellerdetails->theme.'.home',compact('shopid'));
                }else
                {
                    $this->setsession($shopid);
                    // Authentication passed...
                    //return Redirect::back();
                    //return view('frontend.'.$sellerdetails->theme.'.home',compact('shopid'));
                }
                
            }else
            {
                Session::flash('class', 'alert-danger');
                Session::flash('message', 'Email or password does not match!');
                //return Redirect::route("frontend.index");
                //return Redirect::back();
                //return view('frontend.'.$sellerdetails->theme.'.home',compact('shopid'));
            }
        
        return Redirect::route("frontend.index",compact('shopid'));
    }

    public function RegisterUser($shopid,Request $request)
    {
       $sellerdetails =  $this->shopaccess($shopid);

        $request = $request->all();
      
        $messages = array(
                    'email.required'              =>  trans('messages.email.REQUIRED_ERROR'),
                    'password.required'           =>  trans('messages.password.REQUIRED_ERROR'),
            );

        $validator  =   Validator::make(
            $request,
            array(
                'email'              => 'required|email|unique:users,email',
                'password'           => 'required',
            ),$messages
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
       

        }else
        {
            $obj                =    new User();
            $obj->name          =    isset($request['first_name'])?$request['first_name']:''; 
            $obj->first_name    =    isset($request['first_name'])?$request['first_name']:'';
            $obj->last_name     =    isset($request['last_name'])?$request['last_name']:'';
            $obj->email         =    $request['email'];
            $obj->role          =    FRONT_USER_ROLE;
            $obj->seller_id     =    $sellerdetails->id;
            $obj->password      =    Hash::make($request['password']);
            $obj->save();                
            
            $credentials = [
                                'email' => $request['email'],
                                'password' => $request['password'],
                            ];
            
            if (Auth::attempt($credentials)) {
                Session::flash('success',  trans("Users has been created successfully."));  
                 return Redirect::route("frontend.index",compact('shopid'));
            }
            
        }
    }

    public function doLogout($shopid)
    {
       $sellerdetails =  $this->shopaccess($shopid);
       session()->forget('cart');
        \Auth::logout();

        //return view('frontend.'.$sellerdetails->theme.'.home',compact('shopid'));
       // return Redirect::back();
        return Redirect::route("frontend.index",compact('shopid'));
    }

    
    public function changePassword()
    {
        return view('hrms.auth.change');
    }

    public function processPasswordChange(Request $request)
    {
        $password = $request->old;
        $user     = User::where('id', \Auth::user()->id)->first();


        if (Hash::check($password, $user->password)) {
            $user->password = bcrypt($request->new);
            $user->save();
            \Auth::logout();

            return redirect()->to('/')->with('message', 'Password updated! LOGIN again with updated password.');
        } else {
            \Session::flash('flash_message', 'The supplied password does not matches with the one we have in records');

            return redirect()->back();
        }
    }

    /*public function resetPassword()
    {
        return view('admin.auth.reset');
    }*/

    public function frontlogin($shopid)
    {
      $sellerdetails =  $this->shopaccess($shopid);
      
        return view('frontend.'.$sellerdetails->theme.'.login',compact('shopid','sellerdetails'));

    }

    public function frontregister($shopid)
    {
       $sellerdetails =  $this->shopaccess($shopid);
      
        return view('frontend.'.$sellerdetails->theme.'.register',compact('shopid','sellerdetails'));

    }

    public function processPasswordReset(Request $request)
    {
        $email = $request->email;
        $user  = User::where('email', $email)->first();

        if ($user) {
            $string = strtolower(str_random(6));


            $this->mailer->send('hrms.auth.reset_password', ['user' => $user, 'string' => $string], function ($message) use ($user) {
                $message->from('no-reply@dipi-ip.com', 'Digital IP Insights');
                $message->to($user->email, $user->name)->subject('Your new password');
            });

            \DB::table('users')->where('email', $email)->update(['password' => bcrypt($string)]);

            return redirect()->to('/')->with('message', 'Login with your new password received on your email');
        } else {
            return redirect()->to('/')->with('message', 'Your email is not registered');
        }

    }

    public function convertToArray($values)
    {
        $result = [];
        foreach ($values as $key => $value) {
            $result[$key] = $value;
        }

        return $result;
    }


    public function myaccount($shopid)
    {
        $user_id = Auth::user()->id;
        $sellerdetails =  $this->shopaccess($shopid);
        $catalogs_list = $this->_functionCatalogues($sellerdetails->id);
        $orders_list = Orders::where('user_id',$user_id)->where('seller_id',$sellerdetails->id)->get();
        return view('frontend.'.$sellerdetails->theme.'.account',compact('shopid','catalogs_list','orders_list'));
    }

    public function orderView($shopid,$id=0)
    {
        $sellerdetails =  $this->shopaccess($shopid);

        $orders_list = Orders::where('id',$id)->where('seller_id',$sellerdetails->id)->first();
        
        $catalogs_list = $this->_functionCatalogues($sellerdetails->id);

        return view('frontend.'.$sellerdetails->theme.'.orderview',compact('shopid','catalogs_list','orders_list'));
    }

    public function myaccountupdate($shopid=0,Request $request)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        $user_id = Auth::user()->id;
        $request = $request->all();
      
        $messages = [
            'email.required'            => 'email field required.',
            'display_name.required'     => 'Display name field required.',
            'old_password.required'     => 'Current password field required',
            'new_password.required'     => 'New password field required',
            'new_password.min'          => 'New Password must be 8 charactor.',
            'new_password.regex'        => 'Format password must be number,string,special charactor like:(contoh:!@#$%^&*?><).',
            'confirm_password.required'  => 'Confirm Password required',
            'email.unique'              => 'Email already exist.',
            'confirm_password.same'      => 'Confirm Password must be same New Password!',
        ];
        if(!empty($request['new_password']))
        {
           $validator  =   Validator::make(
            $request,
            array(
                'email'              => 'required|email|unique:users,email,'.$user_id.',id',
                'old_password'       => 'required',
                'new_password'       => 'required|min:8',
                'confirm_password'   => 'required|same:password',
                'display_name'       => 'required',
            ),$messages
        ); 
       }else
       {
            $validator  =   Validator::make(
                $request,
                array(
                    'email'              => 'required|email|unique:users,email,'.$user_id.',id',
                    'display_name'       => 'required',
                ),$messages
            );
       }
        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
       

        }else
        {
                
            $update_data =  [
                'name'          => isset($request['display_name'])?$request['display_name']:'', 
                'first_name'    =>    isset($request['first_name'])?$request['first_name']:'',
                'last_name'     =>    isset($request['last_name'])?$request['last_name']:'',
                'email'         =>    $request['email'],
                'role'          =>    FRONT_USER_ROLE,
                'password'      =>    isset($request['new_password'])?Hash::make($request['new_password']):'',
            ];
           $update_data_arr =  array_filter($update_data);
            $obj                =    User::where('id',$user_id)->update($update_data_arr);
           
            Session::flash('success',  trans("Users account has been updated successfully."));
            return Redirect::back();              
            
        }
    }

    private function random_strings($length_of_string)
    {
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($str_result), 0, $length_of_string);
    }

    public function forgotPasswodView($shopid)
    {
       $sellerdetails =  $this->shopaccess($shopid);
        return view('frontend.'.$sellerdetails->theme.'.forgotpassword',compact('shopid','sellerdetails'));
    }

    public function forgotPasswod($shopid, Request $request)
    {

        $request = $request->all();
        $token_id = $this->random_strings(16);
        
        $user = User::where('email',$request['email'])->first();
        //Check user details
        if(!empty($user))
        {
            
            $encrypt = base64_encode($user->id);
            $url = url('/')."/".$shopid."/resetpassword/".$encrypt."?token=".$token_id;

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            
            $headers .= 'From: <'.$user->email.'>' . "\r\n";
            $subject = "Forgot Password Email.";
            $message ="To change your password please click this url.";
            $message .= "<a href='".$url."'>Reset Password</a>";
            $mail =  @mail($user->email,$subject,$message,$headers);

            if($mail)
            {
                $user_data = ['expire_link' => date('Y-m-d h:i:') , 'reset_password' => 'false' , 'remember_token' => $token_id];
                User::where('id', $user->id)->update($user_data);

                $response["status"] = "true";
                $response["message"] = "Please check your email to send reset password link!";
            }
            else
            {
                $response["status"] = "false";
                $response["message"] = "Something went wrong when sending email!";
            }
        }
        else
        {
            $response["status"] = "false";
            $response["message"] = "Your email is not registered";
        }

        echo json_encode($response);die;
    }

    public function resetPassword($shopid, $id)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        $reset_token = $_GET['token'];
        $decrypt_id = base64_decode($id);
        $userDetails = User::where('id',$decrypt_id)->first();
     
        if($userDetails->remember_token == $reset_token && $userDetails->reset_password == 'false')
        {
            return view('frontend.'.$sellerdetails->theme.'.resetpassword',compact('shopid','sellerdetails','id','reset_token'));
        }
        
        else
        {
            Session::flash('class', 'alert-danger');
            Session::flash('message', 'Reset password link is expire!');
            return Redirect::route("frontend.index",compact('shopid'));
        }

    }
    
    public function resetPasswordChange($shopid, Request $request)
    {
        
        $request = $request->all();
        $id = $request['_id']; 
        $token = $request['reset_token']; 
        $decrypt_id = base64_decode($id);

        $userDetails = User::where('id',$decrypt_id)->first();

        if($userDetails->remember_token != $token)
        {
            Session::flash('class', 'alert-danger');
            Session::flash('message', 'Reset passwork link is expire.');
            return Redirect::route("frontend.index",compact('shopid'));
        }

        $validator  =   Validator::make(
            $request,
            array(
                'password' => 'required|digits:8',
                'passconf' => 'required|same:password|digits:8'
            )
        );
        if ($validator->fails())
        {   
            return Redirect::back()
            ->withErrors($validator)->withInput();
        }
        else
        {
            $password = Hash::make($request['password']);
            $user_data = ['password' => $password , 'reset_password' => 'true'];
            $obj = User::where('id', $decrypt_id)->update($user_data);
            if($obj)
            {
                Session::flash('class', 'alert-success');
                Session::flash('message', 'Login with your new password received on your email');
            }
            else
            {
                Session::flash('class', 'alert-danger');
                Session::flash('message', 'Something went wrong.');
            }
            
        }
        return Redirect::route("frontend.index",compact('shopid'));
    }
}
