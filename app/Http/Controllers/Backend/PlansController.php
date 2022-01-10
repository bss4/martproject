<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Plans;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class PlansController extends Controller
{
    
    public $model   =   'Plans';
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
     * Show Planslist.
     *
     */
    public function listPlans()
    {
        return  View::make("admin.$this->model.index");
       
    }



    public function plansJsonListData()
    {

        $data = Plans::get();
      
        return response()->json($data);
    }

    /**
        * Function for display page  for add new Plans page 
        *
        * @param null
        *
        * @return view page. 
    */
    public function addPlans(){
        
        return  View::make("admin.$this->model.add");
    } //end addAdvertisement()

    public function savePlans()
    {

        $request = Request::all();

    	$validator  =   Validator::make(
            Request::all(),
            array(
                'name'               => 'required|unique:plans,name',
                'price'              => 'required|numeric',
                'description'         => 'required',
            )
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            $obj                    = new Plans;
            $obj->name              = $request['name'];
            $obj->price             = $request['price'];
            $obj->description        = $request['description'];
            $obj->save();
        }     

        Session::flash('success',  trans("Plans has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete Plans*/
    public function deletePlans($Id=0)
    {
        $obj    =  Plans::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("Plans has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete Plans*/

    public function editPlans($Id = 0)
    {
        
        $modeldata    =  Plans::where('id',$Id)->first();
        return  View::make("admin.$this->model.edit",compact('modeldata'));
    }

    public function viewPlans($Id = 0)
    {
        
        $modeldata    =  Plans::find($Id);
         return  View::make("admin.$this->model.view",compact('modeldata'));

    }

    public function updatePlans($Id = 0)
    {
        
        $request = Request::all();
      
        $validator  =   Validator::make(
            Request::all(),
            array(
                'name'               => 'required',
                'price'              => 'required|numeric',
                'description'        => 'required',
            )
        );

        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            $obj                    = Plans::findOrfail($Id);
            $obj->name              = $request['name'];
            $obj->price             = $request['price'];
            $obj->description       = $request['description'];
            $obj->save();
        } 
        Session::flash('success',  trans("Plans has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }
}
