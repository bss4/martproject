<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;

use App\User;
use App\Setting;
use Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class SettingController extends Controller
{
    
    public $model   =   'Setting';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        View::share('modelName',$this->model);
    }

    
    public function addCoinSetting(){
        $coinsetting = Setting::select('value')->where('key_value','coin')->first();
        return  View::make("admin.$this->model.addcoin",compact('coinsetting'));
    } //end addTextSetting()

    public function saveCoinSetting()
    {
        $request = Request::all();

        $validator  =   Validator::make(
            Request::all(),
            array(

                'value'            => 'required'
            )
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            $setting_value = Setting::select('value')->where('key_value','coin')->first();
            if(empty($setting_value))
            {
                $obj                    = new Setting;
                $obj->key_value         = 'coin';
                $obj->value             = $request['value'];
                $obj->save();

                Session::flash('success',  trans("CoinSetting has been added successfully.")); 

            }else
            {
                
                Setting::where('key_value','coin')->update(['value'=>$request['value']]);

                Session::flash('success',  trans("CoinSetting has been update successfully.")); 
            }
            
           
        }     
 
        return Redirect::route("$this->model.save");
        
    }
}
