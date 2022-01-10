<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Sellers;
use App\Shopworkinghours;
use App\Themes;
use App\StoreType;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class ThemesController extends Controller
{
    
    public $model   =   'Themes';
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
     * Show Themeslist.
     *
     */
    public function listThemes()
    {
        $modeldata = Themes::orderBy('id','DESC')->get();
        return  View::make("admin.$this->model.index", compact('modeldata'));
    }
    /**
        * Function for display page  for add new Themes page 
        *
        * @param null
        *
        * @return view page. 
    */

    /*Get All Themes As A JSON Data*/
    public function ThemesJsonListData()
    {
        $data = Themes::orderBy('id','desc')->get();

        return response()->json($data);
    } 
    /*End Get All Themes As A JSON Data*/

    public function addThemes(){
        
        return  View::make("admin.$this->model.add");
    } //end addAdvertisement()

    public function saveThemes()
    {
        $request = Request::all();
       
        $messages = array(
                'name.required'  =>  "Theme name field required.",
                'theme_folder_name.required'  =>  "Theme Folder name field required.",
                'theme_folder_name.unique'  =>  "Theme Folder Name already exist.",
            );

    	$validator  =   Validator::make(
            Request::all(),
            array(
                'name'       => 'required',
                'theme_folder_name' => 'required|unique:themes',
                
            ),$messages
        );
        if ($validator->fails())
        {   

            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            $obj = new Themes;

            if(Request::hasFile('theme_image')){

                    $extension  =    Request::file('theme_image')->getClientOriginalExtension();
                    $fileName   =   time().'-theme.'.$extension;
                    if(Request::file('theme_image')->move(THEMES_IMAGE_ROOT_PATH, $fileName)){

                        $obj->theme_image  =  $fileName;
                    }
              }
           
            $obj->name                  = $request['name'];
            $obj->theme_folder_name     = $request['theme_folder_name'];
           
            $obj->save();
        }     

        Session::flash('success',  trans("Themes has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete Themes*/
    public function deleteThemes($Id=0)
    {
        $obj    =  Themes::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("Themes has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete Themes*/

    public function editThemes($Id = 0)
    {
        $modeldata    =  Themes::find($Id);
        return  View::make("admin.$this->model.edit",compact('modeldata'));
    }

    public function viewThemes($Id = 0)
    {
        $modeldata    =  Themes::find($Id);
         return  View::make("admin.$this->model.view",compact('modeldata'));

    }

    public function updateThemes($Id = 0)
    {
        
       $request = Request::all();
       
        $messages = array(
                'name.required'  =>  "Theme name field required.",
                'theme_folder_name.required'  =>  "Theme Folder name field required.",
                'theme_folder_name.unique'  =>  "Theme Folder name already exist.",
            );

        $validator  =   Validator::make(
            Request::all(),
            array(
               
                'theme_folder_name'    => 'required|unique:themes,theme_folder_name,'.$Id,
                'name'             => 'required',
                
            ),$messages
        );
        if ($validator->fails())
        {   

            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
                
               
            
            $obj = Themes::FindOrFail($Id);

            if(Request::hasFile('theme_image')){

                    $extension  =    Request::file('theme_image')->getClientOriginalExtension();
                    $fileName   =   time().'-theme.'.$extension;
                    if(Request::file('theme_image')->move(THEMES_IMAGE_ROOT_PATH, $fileName)){

                        $obj->theme_image                =  $fileName;
                    }
              }
           
            $obj->theme_folder_name = $request['theme_folder_name'];
            $obj->name          = $request['name'];
           
            $obj->save();
             
        }     

        Session::flash('success',  trans("Themes has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }

}
