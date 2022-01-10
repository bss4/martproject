<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\User;
use App\Orders;
use App\Payments;
use App\Products;
use App\Reviews;
use App\Plans;
use App\Sellers;
use App\Stores;
use App\StoreType;
use Carbon\Carbon;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class DashboardController extends Controller
{
    public function dashboard(){
       
		$total_deliverd_orders = Orders::where('status', 'Delivered')->sum('price');
		$total_order_amount =  Orders::selectRaw('count(*) as total_order, sum(price) as total_price')->first();
		return  View::make("admin.dashboard",compact('total_deliverd_orders','total_order_amount'));   
    } 

    private function  week_range($date) {
	  $ts = strtotime($date);
	  $start = strtotime('monday this week ', $ts);
	  $end = strtotime('sunday this week', $ts);
	  return array(date('Y-m-d', $start), date('Y-m-d', $end));
	}

    public function getTotalPackageBasedOnUser()
    {
    	$request = Request::all();
    	$current_date = date('Y-m-d H:i');
    	$type = $request['type'];
    	$start_date = date('Y-m-'."01"); // First day of the month
    	$end_date = date('Y-m-t'); // 't' gets the last day of the month
    	
    	$week = $this->week_range($current_date);
    	$week_start = $week[0];
    	$week_end   = $week[1];
    	$response = array();
        
        $packages = Plans::select('id')->get()->toArray();


    	$all_plans1 = Sellers::select('package_type', DB::raw('count(*) as all_package'))->where('end_package_date', '>=', $current_date)->groupBy('package_type')->get();
      
    	if($type == 'Monthly')
    	{
    		$monthly = Sellers::select('package_type', DB::raw('count(*) as all_package'))->whereBetween('start_package_date', [$start_date, $end_date])->where('end_package_date', '>=', $current_date)->groupBy('package_type')->get();

    		return response()->json([
		    'status'     => 'true',
		    'data'     => $monthly,
		    'type'     => 'Monthly'
		    ]); 
    	}
    	elseif($type == 'Weekly')
    	{
    		$weekly = Sellers::select('package_type', DB::raw('count(*) as all_package'))->whereBetween('start_package_date', [$week_start, $week_end])->where('end_package_date', '>=', $current_date)->groupBy('package_type')->get();

    		return response()->json([
		    'status'     => 'true',
		    'data'     => $weekly,
		    'type'     => 'Weekly'
		    ]); 
    	}
        elseif($type == "Today")
        {
            $today = Sellers::select('package_type', DB::raw('count(*) as all_package'))->where('start_package_date','==', $current_date)->where('end_package_date', '>=', $current_date)->groupBy('package_type')->get();

            return response()->json([
            'status'     => 'true',
            'data'     => $today,
            'type'     => 'Today'
            ]); 
        }
    	else
    	{
    		return response()->json([
		    'status'     => 'true',
		    'data'     => $all_plans1
		    ]);
    	}
	 	 
    }
    public function getTotalSales()
    {
		// Get last 6 months
		$data = array();
		$label = array();
		$temp_date = date('m');
		$prev_date = date('m', strtotime('-5 months'));
		$j=0;
		for ($i = $temp_date; $i >= $prev_date; $i--) {
		  // Show date of last 6 months in month-year format
		 $start_date = date('Y-m-01',strtotime('-'.$j.' months')).' 00:00:00';
		 $end_date   = date('Y-m-t',strtotime('-'.$j.' months')).' 23:59:59';
		 
		 $data[] = Orders::whereIn('status', ['Pending', 'Inprogress', 'Delivered', 'Shipped'])->whereBetween('created_at', [$start_date, $end_date])->sum('price');
		 
		 $label[] = date('M',strtotime('-'.$j.' months')); 
		 $j++;
		}
		

		return response()->json([
		    'status'     => 'true',
		    'data'     => array_reverse($data),
		    'label'    => array_reverse($label)
	    ]);
    }
    public function getTotalStore()
    {
        
        $store_type = Stores::select('store_type', DB::raw('count(*) as all_store'))->with('storetype')->groupBy('store_type')->get();

       $stores = StoreType::get();
       $empty_arr = array(); $color_arr = array();
       $i=1;
       $colors = array(
            '1' => 'progress-bar-info',
            '2' => 'progress-bar-warning',
            '3' => 'progress-bar-primary1',
            '4' => 'progress-bar-info-light',
            '5' => 'progress-bar-warning-light',
            '6' => 'progress-bar-primary-light',
            '7' => 'progress-bar-warning-light'
       );
       foreach($stores as $_store)
       {
            $id = $_store->id;
            $store_arr =Stores::where('store_type', $id)->count();
            array_push($empty_arr,['id' => $id, 'count' => $store_arr, 'name' => $_store->name]);
            
       }

      
        return response()->json([
            'status'     => 'true',
            'data'       => $empty_arr,
            'stores'     => count($stores),
            'colors'     => $colors
        ]);
    }
    public function getTotalOrders()
    {
        $orders = Orders::select('status', DB::raw('count(*) as status_count'))->groupBy('status')->get();
        $total_order = Orders::select(DB::raw('count(*) as count'))->get();
        return response()->json([
            'status'     => 'true',
            'data'       => $orders,
            'total'		=> $total_order
        ]);
    }
}
