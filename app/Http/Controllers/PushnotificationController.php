<?php
namespace App\Http\Controllers;
use App\Sellers;
use Illuminate\Http\Request;
  
class PushnotificationController extends Controller
{
    public function pushnotificationtoall()
    {
      
        $sellerdata = Sellers::where('device_id','!=','')->get();
       foreach ($sellerdata as $value) {

            $url = "https://fcm.googleapis.com/fcm/send";
            $token = $value->device_id;
            $serverKey = 'AAAAzLb_RZI:APA91bGU_S2OhG9tJuoQW8IWhF8zeJ_d1ofjJu_ztVyKZyzYHwSXWaaHWKHAzYmsUq5ql5DM71Z-kHt4iwC57AfROWCSSgVcupAZVI56N1ux4x_Do6Jmdmnj5BkCHGbrgNeuIVc_218a';
            $title = "Notification title";
            $body = "Hello I am from Your php server";
            $notification = array('title' =>$title , 'body' => $body, 'sound' => 'default', 'badge' => '1');
            $arrayToSend = array('to' => $token, 'notification' => $notification,'priority'=>'high');
            $json = json_encode($arrayToSend);
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: key='. $serverKey;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
            //Send the request
            $response = curl_exec($ch);
            //Close request
            if ($response === FALSE) {
            die('FCM Send Error: ' . curl_error($ch));
            }
            curl_close($ch);
       }
        
    }
}
