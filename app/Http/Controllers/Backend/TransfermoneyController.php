<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Sellers;
use App\Orders;
use App\SellerBank;
use App\Transfermoney;
use App\SellerUipdetails;
use App\Http\Requests;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Response,Session,URL,View,Validator,hasFile;

class TransfermoneyController extends Controller
{
    
    public $model   =   'Transfermoney';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        View::share('modelName',$this->model);
    }

    public function trasnfarmoneydirectbank($Id=0)
    {
        
       
        $total_amount = Orders::where('seller_id',$Id)->where('status','Delivered')->where('seller_pay_status','0')->sum('price');

        $SellerBank = SellerBank::where('seller_id',$Id)->first();

        if($total_amount == '0' || empty($SellerBank))
        {

             Session::flash('success',  trans("Pending Amount is Zero or Seller bank details not found.")); 
             return Redirect::back();
        }

        $account_number = config("custom.account_number");
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.razorpay.com/v1/payouts',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
            "account_number":"'.$account_number.'",
            "amount":'.$total_amount.',
            "currency":"INR",
            "mode":"NEFT",
            "purpose":"refund",
            "fund_account":{
                "account_type":"bank_account",
                "bank_account":{
                    "name":"'.$SellerBank->account_holder_name.'",
                    "ifsc":"'.$SellerBank->ifsc_code.'",
                    "account_number":"'.$SellerBank->account_number.'"
                },
                "contact":{
                    "name":"'.$SellerBank->account_holder_name.'",
                    "email":"'.$SellerBank->account_gmail.'",
                    "contact":"'.$SellerBank->account_phone.'",
                    "type":"employee",
                    "reference_id":"Acme Contact ID 12345",
                    "notes":{
                        "notes_key_1":"Tea, Earl Grey, Hot",
                        "notes_key_2":"Tea, Earl Grey… decaf."
                    }
                }
            },
            "queue_if_low_balance":true,
            "reference_id":"Acme Transaction ID 12345",
            "narration":"Acme Corp Fund Transfer",
            "notes":{
                "notes_key_1":"Beam me up Scotty",
                "notes_key_2":"Engage"
            }
        }',
          CURLOPT_HTTPHEADER => array(
            'X-Payout-Idempotency: ',
            'Authorization: Basic cnpwX3Rlc3RfUTVSdTBHUmVObE5tTTc6azdDWVhraXlNbXJtNm1nQmhKclpMYnlH',
            'Content-Type: application/json'
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $jsondata = json_decode($response);
      
        if(isset($jsondata->error->code) && $jsondata->error->code=='BAD_REQUEST_ERROR')
        {
            Session::flash('success',  trans("Something went wrong.")); 
             return Redirect::back();
        }else
        {

            $payment_id     = isset($jsondata->id)?$jsondata->id:'';
            $entity         = isset($jsondata->entity)?$jsondata->entity:'';
            $fund_account_id = isset($jsondata->fund_account_id)?$jsondata->fund_account_id:'';
            $fund_account_entity = isset($jsondata->fund_account->entity)?$jsondata->fund_account->entity:'';
            $fund_account_contact_id = isset($jsondata->fund_account->contact_id)?$jsondata->fund_account->contact_id:'';
            $fund_account_contact_entity = isset($jsondata->fund_account->contact->entity)?$jsondata->fund_account->contact->entity:'';
            $account_type = isset($jsondata->fund_account->account_type)?$jsondata->fund_account->account_type:'';
            $ifsc = isset($jsondata->fund_account->bank_account->ifsc)?$jsondata->fund_account->bank_account->ifsc:'';
            $bank_name = isset($jsondata->fund_account->bank_account->bank_name)?$jsondata->fund_account->bank_account->bank_name:'';
            $name = isset($jsondata->fund_account->bank_account->name)?$jsondata->fund_account->bank_account->name:'';
            $amount = isset($jsondata->amount)?$jsondata->amount:'';
            $currency = isset($jsondata->currency)?$jsondata->currency:'';
            $status = isset($jsondata->status)?$jsondata->status:'';
            $mode = isset($jsondata->mode)?$jsondata->mode:'';   


            $payment_obj = new Transfermoney;
            $payment_obj->seller_id = $Id;
            $payment_obj->transation_id = isset($payment_id)?$payment_id:'';
            $payment_obj->entity = isset($entity)?$entity:'';
            $payment_obj->fund_account_id = isset($fund_account_id)?$fund_account_id:'';
            $payment_obj->fund_account_entity = isset($fund_account_entity)?$fund_account_entity:'';
            $payment_obj->fund_account_contact_id = isset($fund_account_contact_id)?$fund_account_contact_id:'';
            $payment_obj->fund_account_contact_entity = isset($fund_account_contact_entity)?$fund_account_contact_entity:'';
            $payment_obj->account_type = isset($account_type)?$account_type:'';
            $payment_obj->ifsc = isset($ifsc)?$ifsc:'';
            $payment_obj->bank_name = isset($bank_name)?$bank_name:'';
            $payment_obj->name = isset($name)?$name:'';
            $payment_obj->currency = isset($currency)?$currency:'';
            $payment_obj->mode = isset($mode)?$mode:'';
            $payment_obj->amount = isset($amount)?$amount:0;
            $payment_obj->status = $status;
            $payment_obj->save();

            Orders::where('seller_id',$seller_id)->where('status','Delivered')->update(['seller_pay_status'=>'1']);
            Session::flash('success',  trans("Transation successfully.")); 
            return Redirect::back();
        }

    }

    public function trasfermoneyupi($Id=0)
    {
        $sellerdetails = Sellers::where('id',$Id)->first();
        $total_amount = Orders::where('seller_id',$Id)->where('status','Delivered')->where('seller_pay_status','0')->sum('price');

        $SellerBank = SellerUipdetails::where('seller_id',$Id)->first();

        if($total_amount == '0' || empty($SellerBank))
        {

             Session::flash('success',  trans("Pending Amount is Zero or Seller bank details not found.")); 
             return Redirect::back();
        }

        if(isset($SellerBank->active_upi) && $SellerBank->active_upi=='googlepay' && $SellerBank->google_pay_isverify=='verified')
        {
            $upi_account_id = $SellerBank->google_pay_id;
        }

        if(isset($SellerBank->active_upi) && $SellerBank->active_upi=='phonepay' && $SellerBank->phone_pay_isverify=='verified')
        {
            $upi_account_id = $SellerBank->phone_pay_id;
        }

        if(isset($SellerBank->active_upi) && $SellerBank->active_upi=='paytm' && $SellerBank->paytm_isverify=='verified')
        {
            $upi_account_id = $SellerBank->paytm_id;
        }

        if(isset($SellerBank->active_upi) && $SellerBank->active_upi=='other' && $SellerBank->others_verify=='verified')
        {
            $upi_account_id = $SellerBank->others_id;
        }

         $account_number = config("custom.account_number");


            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.razorpay.com/v1/payouts',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
            "account_number":"'.$account_number.'",
            "amount":'.$total_amount.',
            "currency":"INR",
            "mode":"UPI",
            "purpose":"refund",
            "fund_account":{
                "account_type":"vpa",
                "vpa":{
                    "address":"'.$upi_account_id.'"
                },
                "contact":{
                    "name":"'.$sellerdetails->name.'",
                    "email":"'.$sellerdetails->email.'",
                    "contact":"'.$sellerdetails->phone.'",
                    "type":"employee",
                    "reference_id":"Acme Contact ID 12345",
                    "notes":{
                        "notes_key_1":"Tea, Earl Grey, Hot",
                        "notes_key_2":"Tea, Earl Grey… decaf."
                    }
                }
            },
            "queue_if_low_balance":true,
            "reference_id":"Acme Transaction ID 12345",
            "narration":"Acme Corp Fund Transfer",
            "notes":{
                "notes_key_1":"Beam me up Scotty",
                "notes_key_2":"Engage"
            }
            }',
            CURLOPT_HTTPHEADER => array(
            'X-Payout-Idempotency: ',
            'Authorization: Basic cnpwX3Rlc3RfUTVSdTBHUmVObE5tTTc6azdDWVhraXlNbXJtNm1nQmhKclpMYnlH',
            'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

        $jsondata = json_decode($response);
      
        if(isset($jsondata->error->code) && $jsondata->error->code=='BAD_REQUEST_ERROR')
        {
            Session::flash('success',  trans("Something went wrong.")); 
             return Redirect::back();
        }else
        {

            $payment_id     = isset($jsondata->id)?$jsondata->id:'';
            $entity         = isset($jsondata->entity)?$jsondata->entity:'';
            $fund_account_id = isset($jsondata->fund_account_id)?$jsondata->fund_account_id:'';
            $fund_account_entity = isset($jsondata->fund_account->entity)?$jsondata->fund_account->entity:'';
            $fund_account_contact_id = isset($jsondata->fund_account->contact_id)?$jsondata->fund_account->contact_id:'';
            $fund_account_contact_entity = isset($jsondata->fund_account->contact->entity)?$jsondata->fund_account->contact->entity:'';
            $account_type = isset($jsondata->fund_account->account_type)?$jsondata->fund_account->account_type:'';
            $vpa_username = isset($jsondata->fund_account->vpa->username)?$jsondata->fund_account->vpa->username:'';
            $vpa_handle = isset($jsondata->fund_account->vpa->handle)?$jsondata->fund_account->vpa->handle:'';
            $vpa_address = isset($jsondata->fund_account->vpa->address)?$jsondata->fund_account->vpa->address:'';
          
            $amount = isset($jsondata->amount)?$jsondata->amount:'';
            $currency = isset($jsondata->currency)?$jsondata->currency:'';
            $status = isset($jsondata->status)?$jsondata->status:'';
            $mode = isset($jsondata->mode)?$jsondata->mode:'';   


            $payment_obj = new Transfermoney;
            $payment_obj->seller_id = $Id;
            $payment_obj->transation_id = isset($payment_id)?$payment_id:'';
            $payment_obj->entity = isset($entity)?$entity:'';
            $payment_obj->fund_account_id = isset($fund_account_id)?$fund_account_id:'';
            $payment_obj->fund_account_entity = isset($fund_account_entity)?$fund_account_entity:'';
            $payment_obj->fund_account_contact_id = isset($fund_account_contact_id)?$fund_account_contact_id:'';
            $payment_obj->fund_account_contact_entity = isset($fund_account_contact_entity)?$fund_account_contact_entity:'';
            $payment_obj->account_type = isset($account_type)?$account_type:'';
            $payment_obj->ifsc = isset($ifsc)?$ifsc:'';
            $payment_obj->bank_name = isset($bank_name)?$bank_name:'';
            $payment_obj->name = isset($name)?$name:'';
            $payment_obj->currency = isset($currency)?$currency:'';
            $payment_obj->mode = isset($mode)?$mode:'';
            $payment_obj->amount = isset($amount)?$amount:0;
            $payment_obj->status = $status;
            $payment_obj->save();

            Orders::where('seller_id',$Id)->where('status','Delivered')->update(['seller_pay_status'=>'1']);
            Session::flash('success',  trans("Transation successfully.")); 
            return Redirect::back();
        }

    }

    public function verifyupi()
    {

        $Id = $_POST['id'];
        $type = $_POST["type"];
        $accountNumber = $_POST["acc_number"];
       
        $sellerdetails = Sellers::where('id',$Id)->first();
        
        $SellerBank = SellerUipdetails::where('seller_id',$Id)->first();
        if(empty($SellerBank))
        {

            $response = array('status' => 'false', 'message' => "Seller Upi details not found."); 
            echo json_encode($response);
            die;
        }

        if($type=='googlepay')
        {
            $upi_account_id = $SellerBank->google_pay_id;
        }
        
        if($type=='phonepay')
        {
            $upi_account_id = $SellerBank->phone_pay_id;
        }
        
        if($type=='paytm')
        {
            $upi_account_id = $SellerBank->paytm_id;
        }

        if($type=='other')
        {
            $upi_account_id = $SellerBank->others_id;
        }
        


         


            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.razorpay.com/v1/payouts',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
            "account_number":"'.$accountNumber.'",
            "amount":100,
            "currency":"INR",
            "mode":"UPI",
            "purpose":"refund",
            "fund_account":{
                "account_type":"vpa",
                "vpa":{
                    "address":"'.$upi_account_id.'"
                },
                "contact":{
                    "name":"'.$sellerdetails->name.'",
                    "email":"'.$sellerdetails->email.'",
                    "contact":"'.$sellerdetails->phone.'",
                    "type":"employee",
                    "reference_id":"Acme Contact ID 12345",
                    "notes":{
                        "notes_key_1":"Tea, Earl Grey, Hot",
                        "notes_key_2":"Tea, Earl Grey… decaf."
                    }
                }
            },
            "queue_if_low_balance":true,
            "reference_id":"Acme Transaction ID 12345",
            "narration":"Acme Corp Fund Transfer",
            "notes":{
                "notes_key_1":"Beam me up Scotty",
                "notes_key_2":"Engage"
            }
            }',
            CURLOPT_HTTPHEADER => array(
            'X-Payout-Idempotency: ',
            'Authorization: Basic cnpwX3Rlc3RfUTVSdTBHUmVObE5tTTc6azdDWVhraXlNbXJtNm1nQmhKclpMYnlH',
            'Content-Type: application/json'
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

        $jsondata = json_decode($response);
        
        if(isset($jsondata->error->code) && $jsondata->error->code=='BAD_REQUEST_ERROR')
        {
            
            $response = array('status' => 'false', 'message' => "Something went wrong."); 
            echo json_encode($response);
            die;
        }else
        {

            $payment_id     = isset($jsondata->id)?$jsondata->id:'';
            $entity         = isset($jsondata->entity)?$jsondata->entity:'';
            $fund_account_id = isset($jsondata->fund_account_id)?$jsondata->fund_account_id:'';
            $fund_account_entity = isset($jsondata->fund_account->entity)?$jsondata->fund_account->entity:'';
            $fund_account_contact_id = isset($jsondata->fund_account->contact_id)?$jsondata->fund_account->contact_id:'';
            $fund_account_contact_entity = isset($jsondata->fund_account->contact->entity)?$jsondata->fund_account->contact->entity:'';
            $account_type = isset($jsondata->fund_account->account_type)?$jsondata->fund_account->account_type:'';
            $vpa_username = isset($jsondata->fund_account->vpa->username)?$jsondata->fund_account->vpa->username:'';
            $vpa_handle = isset($jsondata->fund_account->vpa->handle)?$jsondata->fund_account->vpa->handle:'';
            $vpa_address = isset($jsondata->fund_account->vpa->address)?$jsondata->fund_account->vpa->address:'';
          
            $amount = isset($jsondata->amount)?$jsondata->amount:'';
            $currency = isset($jsondata->currency)?$jsondata->currency:'';
            $status = isset($jsondata->status)?$jsondata->status:'';
            $mode = isset($jsondata->mode)?$jsondata->mode:'';   


            $payment_obj = new Transfermoney;
            $payment_obj->seller_id = $Id;
            $payment_obj->transation_id = isset($payment_id)?$payment_id:'';
            $payment_obj->entity = isset($entity)?$entity:'';
            $payment_obj->fund_account_id = isset($fund_account_id)?$fund_account_id:'';
            $payment_obj->fund_account_entity = isset($fund_account_entity)?$fund_account_entity:'';
            $payment_obj->fund_account_contact_id = isset($fund_account_contact_id)?$fund_account_contact_id:'';
            $payment_obj->fund_account_contact_entity = isset($fund_account_contact_entity)?$fund_account_contact_entity:'';
            $payment_obj->account_type = isset($account_type)?$account_type:'';
            $payment_obj->ifsc = isset($ifsc)?$ifsc:'';
            $payment_obj->bank_name = isset($bank_name)?$bank_name:'';
            $payment_obj->name = isset($name)?$name:'';
            $payment_obj->currency = isset($currency)?$currency:'';
            $payment_obj->mode = isset($mode)?$mode:'';
            $payment_obj->amount = isset($amount)?$amount:0;
            $payment_obj->status = $status;
            $payment_obj->save();

            if($type=='googlepay')
            {
               
                SellerUipdetails::where('seller_id',$Id)->update(['google_pay_isverify'=>'verified']);
            }
            
            if($type=='phonepay')
            {
               SellerUipdetails::where('seller_id',$Id)->update(['phone_pay_isverify'=>'verified']);
            }
            
            if($type=='paytm')
            {
                SellerUipdetails::where('seller_id',$Id)->update(['paytm_isverify'=>'verified']);
            }

            if($type=='other')
            {
                SellerUipdetails::where('seller_id',$Id)->update(['others_verify'=>'verified']);
            }
            $response = array('status' => 'true', 'message' => $type.' Verify successfully.'); 
            echo json_encode($response);
            die;
        }

    }
}
