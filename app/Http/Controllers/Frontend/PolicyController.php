<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController as BaseController;
use App\User;
use App\Sellers;
use App\Stores;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Mail\Mailer;
use App\Http\Requests;
use Illuminate\Support\Facades\Hash;
use Auth,Blade,Config,Cache,Cookie,DB,File,Mail,mongoDate,Redirect,Response,Session,URL,View,Validator,hasFile;

class PolicyController extends BaseController
{
    
    public $model   =   'Policy';
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
     * Show Policylist.
     *
     */
    public function paymentpolicy($shopid)
    {
       
       $sellerdetails =  $this->shopaccess($shopid);
       $catalogs_list = $this->_functionCatalogues($sellerdetails->id);

       $storedata = Stores::where('seller_id',$sellerdetails->id)->first();
       $payments_policy = $storedata->payments_policy;

       return view('frontend.'.$sellerdetails->theme.'.paymentpolicy',compact('shopid','catalogs_list','payments_policy','sellerdetails'));
    }

    public function privacypolicy($shopid)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        $catalogs_list = $this->_functionCatalogues($sellerdetails->id);

        $storedata = Stores::where('seller_id',$sellerdetails->id)->first();
        $privacy_policy = $storedata->privacy_policy;

        return view('frontend.'.$sellerdetails->theme.'.privacypolicy',compact('shopid','catalogs_list','privacy_policy','sellerdetails'));
    }

    public function termsconditions($shopid)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        $catalogs_list = $this->_functionCatalogues($sellerdetails->id);

        $storedata = Stores::where('seller_id',$sellerdetails->id)->first();
        $terms_conditions = $storedata->terms_conditions;

        return view('frontend.'.$sellerdetails->theme.'.termsconditions',compact('shopid','catalogs_list','terms_conditions','sellerdetails'));
    }

    public function shippingpolicy($shopid)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        $catalogs_list = $this->_functionCatalogues($sellerdetails->id);

        $storedata = Stores::where('seller_id',$sellerdetails->id)->first();
        $shipping_policy = $storedata->shipping_policy;

        return view('frontend.'.$sellerdetails->theme.'.shippingpolicy',compact('shopid','catalogs_list','shipping_policy','sellerdetails'));
    }

    public function returnrefund($shopid)
    {
        $sellerdetails =  $this->shopaccess($shopid);
        $catalogs_list = $this->_functionCatalogues($sellerdetails->id);

        $storedata = Stores::where('seller_id',$sellerdetails->id)->first();
        $return_refund_policy = $storedata->return_refund_policy;

        return view('frontend.'.$sellerdetails->theme.'.returnrefund',compact('shopid','catalogs_list','return_refund_policy','sellerdetails'));
    }

}
