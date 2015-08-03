<?php

class currencyController extends BaseController {

    public $message = "";
    public function jsonManiulateCurrency()
    {
        $i = Input::get('info'); 
        $cm = new CurrencyManipulation($i['id']);
        $ruleDesign = ($i['currencyIdOrig'] ==  $i['currencyId']) ? 1 : 0 ;
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
    
    public function jsonQueryCurrency()
    {
        
         
        $mode = Input::get('mode');
        
        if($mode == 'collection')
        {

            $currency = Currency :: select('*');
            return Datatables::of($currency)
                ->addColumn('link', function ($currenc) {
                    if(Auth::user()->can('allow_edit')) return '<span onclick="editCurrency(\''.$currenc->currencyId.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                })
                ->editColumn('currencyId', function ($currenc) {
                  //  return  date("Y-m-d", $ip->from);
                    return $currenc->currencyId;
                })
                ->editColumn('currencyName', function ($currenc) {
                   // return  date("Y-m-d", $ip->to);
                    return $currenc->currencyName;
                })
                ->make(true);
             
        }
        elseif($mode == 'single')
        {
            $currency = Currency::where('currencyId', Input::get('currencyId'))->first();
           // $country->from = date("Y-m-d", $ipf->from);
          //  $country->to = date("Y-m-d", $ipf->to);
          //  $country->countryId = $country->countryId;
          //  $country->countryName = $country->countryName;
            //If the field in database is not found , just put the -> here
        }
        
        return Response::json($currency);
    }
    
    public function countryValidation($e,$rulesNum)
    {
        $rules = [
	            'currencyId' => 'required|alpha|size:2|unique:currencies', // the name of countries should be same the one in database
	            'currencyName' => 'required|alpha',
	        ];
        
       $rules1 = [
	            'currencyId' => 'required|alpha|size:2', // the name of countries should be same the one in database
	            'currencyName' => 'required|alpha',
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