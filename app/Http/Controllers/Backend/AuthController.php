<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Mail\Mailer;
use App\Http\Requests;
use Illuminate\Support\Facades\Hash;
use Auth,Blade,Config,Cache,Cookie,DB,File,Mail,mongoDate,Redirect,Response,Session,URL,View,Validator,hasFile;

class AuthController extends Controller
{

    public $model   =   'Dashboard';
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
        View::share('modelName',$this->model);
    }

    public function showLogin()
    {
       return view('admin.auth.login');
    }


    public function doLogin(Request $request)
    {
            request()->validate([
                'email' => 'required',
                'password' => 'required',
                ]);
            
            $remember   =   (!empty($request->remember)) ? true : false; 
            $credentials = $request->only('email', 'password');
            
            $user = User::where('email',$request->email)->where('role',ADMIN_ROLE)->first();
            if(empty($user))
            {
                
                Session::flash('class', 'alert-danger');
                Session::flash('message', 'you can not access Admin Dashboard!');
                return Redirect::route("admin.index");
                die;
              
            }

            if (Auth::attempt($credentials)) {
                
                if($remember == true){  
                    $rememberData['email']          = $request->email;
                    $rememberData['password']       = bcrypt($request->password);
                    $rememberData['remember_me']    = $remember;
                    setcookie('remember_admin',json_encode($rememberData), time() + (86400 * 30)); 
                }else{
                    setcookie('remember_admin','', time() - (86400 - 30)    ); 
                }

                if(Auth::user()->role == ADMIN_ROLE)
                {
                    // Authentication passed...
                     return redirect()->intended('admin/dashboard');
                }else
                {
                    // Authentication passed...
                    return redirect()->route('home');
                }
                
            }else
            {
                Session::flash('class', 'alert-danger');
                Session::flash('message', 'Email or password does not match!');
                 return Redirect::route("admin.index");
            }
        
        
    }

    public function doLogout()
    {
        \Auth::logout();
        return redirect()->to('/admin/adminlogin');
    }

    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function welcome()
    {
        return view('admin.auth.welcome');
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

    public function resetPassword()
    {
        return view('admin.auth.reset');
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


}
