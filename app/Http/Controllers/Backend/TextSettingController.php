<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;

use App\User;
use App\TextSetting;
use Auth,Blade,Config,Cache,Cookie,DB,File,Hash,Mail,mongoDate,Redirect,Request,Response,Session,URL,View,Validator,hasFile;

class TextSettingController extends Controller
{
    
    public $model   =   'TextSetting';
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
     * Show userlist.
     *
     */
    public function listTextSetting()
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

        $DB =   TextSetting::query();
        if(isset($conditionArray) && !empty($conditionArray)){
            $DB->where($conditionArray);
        }
        $DB->orderBy($sortBy,$order);

        $modeldata     =   $DB->paginate((int)$limit);
        
        return  View::make("admin.$this->model.index",compact('limit','breadcrumbs','modeldata','searchVariable','sortBy','order'));

       /* $modeldata =TextSetting::orderBy('id','DESC')->get();
        return  View::make("admin.$this->model.index",compact('modeldata'));*/
    }
    /**
        * Function for display page  for add new user page 
        *
        * @param null
        *
        * @return view page. 
    */
    public function addTextSetting(){
        return  View::make("admin.$this->model.add");
    } //end addTextSetting()

    public function saveTextSetting()
    {
        $request = Request::all();

        $validator  =   Validator::make(
            Request::all(),
            array(
                'key'              => 'required',
                'value'            => 'required'
            )
        );
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            $obj                    = new TextSetting;
            $obj->key_value         = trim($request['key']);
            $obj->value             = $request['value'];
            $obj->save();
            $queries = DB::getQueryLog();
            $this->textFileWrite();
        }     

        Session::flash('success',  trans("TextSetting has been added successfully."));  
        return Redirect::route("$this->model.index");
        
    }

    /*delete user*/
    public function deleteTextSetting($Id=0)
    {
        $obj    =  TextSetting::find($Id);
       
        if($obj->delete())
        {
           Session::flash('success', trans("TextSetting has been deleted successfully."));
           $this->textFileWrite();
        }
        else{
           Session::flash('error',trans("Something went wrong.")); 
        }

        return Redirect::route("$this->model.index");

    }
    /*end delete user*/

    public function editTextSetting($Id = 0)
    {
        $modeldata    =  TextSetting::find($Id);
        return  View::make("admin.$this->model.edit",compact('modeldata'));
    }

    public function updateTextSetting($Id = 0)
    {
        $request=Request::all();
        $validator  =   Validator::make(
            Request::all(),
            array(
                'value'   => 'required'
            )
        );
        
        if ($validator->fails())
        {   
            return Redirect::back()
                ->withErrors($validator)->withInput();
        }else{
            
            $obj                    = TextSetting::findOrfail($Id);
            $obj->value             = trim($request['value']);
            $obj->save();

            $this->textFileWrite();
        }     

        Session::flash('success',  trans("TextSetting has been updated successfully."));  
        return Redirect::route("$this->model.index");
    }

    public function textFileWrite($type = null){
            $list       = TextSetting::get()->toArray();
            $currLangArray = '<?php return array(';
            
            foreach ($list as $listDetails) {
                    $currLangArray .= '"' . $listDetails['key_value'] . '"=>"' . $listDetails['value'] . '",' . "\n";
                
            }
            
            $currLangArray .= ');';
            
            $file = ROOT . DS . 'resources' . DS . 'lang' . DS . 'en' . DS . 'messages.php';
            
            
            $bytes_written = File::put($file, $currLangArray);
            if ($bytes_written === false) {
                die("Error writing to file");
            }
        
        
    } //end textFileWrite()
}
