<?php

class countryController extends BaseController {

    public $message = "";
    public function jsonManiulateCountry()
    {
        $i = Input::get('info'); 
        $cm = new CountryManipulation($i['id'], $i);
        $ruleDesign = ($i['countryIdOrig'] ==  $i['countryId']) ? 1 : 0 ;
        if(empty($this->countryValidation($i,$ruleDesign)))
        {
            $cm->save1($i);
        }else
        {
            $errorMessage = "";
            foreach($this->message as $a)
            {
                $errorMessage .= "$a\n";
            }
            return $errorMessage;
        }
        
    }
    
    public function jsonQueryCountry()
    {
        
         
        $mode = Input::get('mode');
        
        if($mode == 'collection')
        {

            $country = Country :: select('*');
            return Datatables::of($country)
                ->addColumn('link', function ($countr) {
                     if(Auth::user()->can('allow_edit')) return '<span onclick="editCountry(\''.$countr->countryId.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                })
                ->editColumn('countryId', function ($countr) {
                  //  return  date("Y-m-d", $ip->from);
                    return $countr->countryId;
                })
                ->editColumn('countryName', function ($countr) {
                   // return  date("Y-m-d", $ip->to);
                    return $countr->countryName;
                })
                ->make(true);
             
        }
        elseif($mode == 'single')
        {
            $country = Country::where('countryId', Input::get('countryId'))->first();
           // $country->from = date("Y-m-d", $ipf->from);
          //  $country->to = date("Y-m-d", $ipf->to);
          //  $country->countryId = $country->countryId;
          //  $country->countryName = $country->countryName;
            //If the field in database is not found , just put the -> here
        }
        
        return Response::json($country);
    }
    
    public function countryValidation($e,$rulesNum)
    {
        $rules = [
	            'countryId' => 'required|alpha|size:2|unique:countries', // the name of countries should be same the one in database
	            'countryName' => 'required|alpha',
	        ];
        
       $rules1 = [
	            'countryId' => 'required|alpha|size:2', // the name of countries should be same the one in database
	            'countryName' => 'required|alpha',
	        ]; 
         $ruleStore = ($rulesNum == 1) ? $rules1 : $rules;
         $validator = Validator::make($e, $ruleStore);
	 //$validator = Validator::make(Input::all(), $rules);
	 if ($validator->fails())
	  {
	       $this->message = $validator->messages()->all();
	           // return Redirect::action('UserController@authenticationProcess')->with('flash_error', 'Invalid Credential. Please try again');
	  }
          return $this->message;
    }
    

}