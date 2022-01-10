<?php


namespace App\Http\Controllers;

use App\Sellers;
use App\Catalogue;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;


class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
    	$response = [
            'success' => true,
            'statuscode'=>200,
            'data'    => $result,
            'message' => $message,
        ];


        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 201)
    {
    	$response = [
            'success' => false,
            'statuscode'=>201,
            'message' => $error,
        ];


        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }

    public function shopaccess($shopid)
    {

        $sellerdetails = Sellers::where('app_id',$shopid)->first();
        if($sellerdetails)
        {
           return $sellerdetails;

        }else
        {
           return false;
        }
        
    }

    public function _functionCatalogues($seller_id)
    {
        $catalogs_list = Catalogue::where('seller_id',$seller_id)->where('status',CATALOGUE_ACTIVE)->get();
        if($catalogs_list)
        {
            return $catalogs_list;
        }else
        {
            return false;
        }

    }

    public function combinations($arrays, $i = 0) {
        if (!isset($arrays[$i])) {
            return array();
        }
        if ($i == count($arrays) - 1) {
            return $arrays[$i];
        }

        // get combinations from subsequent arrays
        $tmp = $this->combinations($arrays, $i + 1);

        $result = array();

        // concat each array from tmp with each element from $arrays[$i]
        foreach ($arrays[$i] as $v) {
            foreach ($tmp as $t) {
                $result[] = is_array($t) ?array_merge(array($v), $t) : array($v, $t);
            }
        }

        return $result;
    }

}