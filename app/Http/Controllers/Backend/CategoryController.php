<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Category;
use App\StoreType;
use Breadcrumbs,Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class CategoryController extends Controller
{
    
    public $model   =   'Category';
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
    public function listCategory()
    {
        

        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        ### breadcrumbs End ###
        $conditionArray=array();
        $formData=Request::input();
        $searchVariable =   array(); 
        if ((Request::all() && isset($formData['display'])) || isset($formData['page']) ) {
            $searchData =   Request::all();
            unset($searchData['display']);
            unset($searchData['_token']);
            unset($searchData['sortBy']);
            unset($searchData['order']);
            
            if(isset($searchData['page'])){
                unset($searchData['page']);
            }
            
            if(isset($searchData['records_per_page'])){
                unset($searchData['records_per_page']);
            }
            foreach($searchData as $fieldName => $fieldValue){
                $fieldValue =   trim($fieldValue);
                if($fieldValue != ''){
                    if($fieldName == 'active' ){
                        $conditionArray[] =  ['is_active',(int)$fieldValue];
                    }
                    else{
                        $conditionArray[] =  [$fieldName, 'LIKE', '%'.$fieldValue.'%'];
                    }
                }
                $searchVariable =   array_merge($searchVariable,array($fieldName => $fieldValue));
            }
        }
        
        if(Request::get('records_per_page')!=''){
            $searchVariable =   array_merge($searchVariable,array('records_per_page' => Request::get('records_per_page')));
        }
        $sortBy = (Request::input('sortBy')) ? Request::input('sortBy') : 'created_at';
        $order  = (Request::input('order')) ? Request::input('order')   : 'DESC';
        $limit          =   (Request::get('records_per_page')!='') ? Request::get('records_per_page'):RECORDS_PER_PAGE; 

        $DB =   Category::query();
        if(isset($conditionArray) && !empty($conditionArray)){
            $DB->where($conditionArray);
        }
        //$DB->where('role','!=',ADMIN_ROLE);
        $DB->orderBy($sortBy,$order);

        $modeldata     =   $DB->paginate((int)$limit);
        
        return  View::make("admin.$this->model.index",compact('limit','breadcrumbs','modeldata','searchVariable','sortBy','order'));
    }

    public function CategoryJsonListData()
    {

        $data = Category::get();
        
        return response()->json($data);
    }

    /**
        * Function for display page  for add new Plans page 
        *
        * @param null
        *
        * @return view page. 
    */
    public function addCategory(){
        $storetype = StoreType::get();
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_add"),'');
        $storetype = StoreType::get();
        return  View::make("admin.$this->model.add",compact('breadcrumbs','storetype'));
    } //end addAdvertisement()

    public function saveCategory()
    {
        $request = Request::all();

    	$validator  =   Validator::make(
            Request::all(),
            array(
                'name'               => 'required',
                'storetype'          => 'required',
            )
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            $obj                    = new Category;
            $obj->store_type_id     = $request['storetype'];
            $obj->name              = $request['name'];
            $obj->save();
        }     

        Session::flash('success',  trans("Category has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete Plans*/
    public function deleteCategory($Id=0)
    {
        $obj    =  Category::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("Category has been deleted successfully."));
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete Plans*/

    public function editCategory($Id = 0)
    {
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_edit"),'');

        $modeldata    =  Category::find($Id);
        $storetype = StoreType::get();
        return  View::make("admin.$this->model.edit",compact('modeldata','breadcrumbs','storetype'));
    }

    public function viewCategory($Id = 0)
    {
        $breadcrumbs[] = array(trans("messages.global.breadcrumbs_dashboard"),route('admin.dashboard'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_module"),route($this->model.'.index'));
        $breadcrumbs[] = array(trans("messages.$this->model.breadcrumbs_edit"),'');

        $modeldata    =  Plans::find($Id);
         return  View::make("admin.$this->model.view",compact('modeldata','breadcrumbs'));

    }

    public function updateCategory($Id = 0)
    {
        
        $request = Request::all();
      
        $validator  =   Validator::make(
            Request::all(),
            array(
                'name'               => 'required',
                'storetype'          => 'required',
            )
        );

        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            $obj                    = Category::findOrfail($Id);
            $obj->name              = $request['name'];
            $obj->store_type_id     = $request['storetype'];
            $obj->save();
        } 
        Session::flash('success',  trans("Category has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }
}
