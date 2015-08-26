<?php

class SupplierController extends BaseController
{
    public $message = "";
 
    public function jsonQuerySupplier()
    {
        $mode = Input::get('mode');
        if ($mode == 'collection') {
            $filter = Input::get('filterData');
            $filterId = "supplierCode";
            $filterOrder = "";
            if($filter["sorting"] != "")
                $filterId = $filter["sorting"];
                $filterOrder = $filter["current_sorting"];
                
            //if (!isset($filter['zone']['zoneId']))
             //   $filter['zone']['zoneId'] = '';
          // $supplier = Supplier :: select('*');
      
       //   Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);
             //select(['ipfId','from', 'to', 'size']);
          $supplier = Supplier::select(['supplierCode','supplierName','address','phone_1','phone_2','email','countries.countryName','currencies.currencyName','suppliers.currencyId','creditDay','creditLimit','status','contactPerson_1','contactPerson_2','suppliers.updated_at','suppliers.updated_by','payment','location'])
           ->leftJoin('countries', function($join) {
                $join->on('countries.countryId', '=','suppliers.countryId');
            })
            ->leftJoin('currencies', function($joins) {
                $joins->on('currencies.currencyId', '=','suppliers.currencyId');  
            })
            ->Orderby($filterId,$filterOrder);
   
           
            if ($filter['status'] == 99) {
                $supplier->onlyTrashed();
            } else if ($filter['status'] != 100) {
               $supplier->where('status', $filter['status']);
            }

         //To query all together , use >where(function ($query)

             $supplier->where(function ($query) use ($filter) {
                $query
                    ->where('supplierName', 'LIKE', '%' . $filter['name'] . '%')
                    ->where(function ($query) use ($filter) 
                     {
                        $query->orwhere('phone_1', 'LIKE', '%' . $filter['phone'] . '%')
                              ->orwhere('phone_2', 'LIKE', '%' . $filter['phone'] . '%');
                     })
                    ->where(function ($query) use ($filter) 
                     {
                        $query->orwhere('contactPerson_1', 'LIKE', '%' . $filter['contact'] . '%')
                               ->orwhere('contactPerson_2', 'LIKE', '%' . $filter['contact'] . '%');
                     })
                    ->where('supplierCode', 'LIKE', '%' . $filter['id'] . '%')
					 ->where('countryName', 'LIKE', '%' .$filter['country'] . '%');
            });
            

      //  ,'email','countries.countryName','currencies.currencyName','creditDay','creditLimit','status','contactPerson_1','contactPerson_2','suppliers.updated_at','suppliers.updated_by')
             return Datatables::of($supplier)
                ->addColumn('link', function ($supplie) {
                    return '<span onclick="editSupplier(\''.$supplie->supplierCode.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                })
              
                 ->editColumn('status', function ($supplie) {
                   // return  date("Y-m-d", $ip->to);
                    return ($supplie->status == '1') ? $supplie->status = '正常' : $supplie->status = '暫停';
                })
                ->make(true);
      
                
        } else if ($mode == 'single') {
            $supplier = Supplier::where('supplierCode', Input::get('supplierCode'))->first();
        } else if ($mode == 'checkId') {
           $supplier = Supplier::select('supplierCode')->where('supplierCode', Input::get('supplierCode'))->first();
            $supplier = count($supplier);
        }
       // $store['data'] = $supplier;
        return Response::json($supplier);
    }
    
    public function jsonCheckSupplier()
    {
          # Request
        // $time_start = microtime(true);
        $keyword = Input::has('supplierCode') && Input::get('supplierCode') != '' ? Input::get('supplierCode') : 'na'; // 

            // $keyword = str_replace(array('?', '*'), '%', $keyword);
        $supplier = Supplier::select('supplierCode','supplierName','address','address1','address2','phone_1','phone_2','suppliers.email','countryId','fax_1','fax_2','payment','currencyId','creditDay','creditLimit','creditAmount','status','contactPerson_1','contactPerson_2','suppliers.updated_at','suppliers.updated_by','remark','users.username','location')->where('supplierCode', Input::get('supplierCode'))
            ->join('users', function($joinss) {
                $joinss->on('users.id', '=','suppliers.updated_by');  
            })
            ->first();
           // $store = date("Y-m-d", $supplier->updated_at);
            $supplier['format_date'] = $supplier->updated_at;
            return Response::json($supplier);
            
            

    }
    
    public function jsonUpdate()
    {

        $i = Input::get('supplierinfo');

        if(empty($this->doValidation($i)))
        { 
            //Do validation 
             $cm = new supplierManipulation($i['supplierCode'],$i);
            
            $id = $cm->save($i);

            return Response::json(['mode' => ($i['supplierCode'] == $id ? 'update' : 'create'), 'id' => $id]);
            
        }else
        {
            
            $errorMessage = "";
            foreach($this->message as $a)
            {
                $errorMessage .= "$a\n";
            }
            return $errorMessage;
           // return Response::json($errorMessage);
        }
        
 
    }
    
    
    
    public function doValidation($e)
    {

         $rules = [
	            'supplierName' => 'required',
	            'creditDay' => 'min:0',
                    'creditLimit' => 'min:0',
                    'creditAmount' => 'min:0',
                    'countryId' => 'required',
                    'currencyId' => 'required',
                    'status'=> 'required',
                    'payment' => 'required',
                    'phone_1' => 'size:8',
                    'phone_2' => 'min:8',
                    'fax_1' => 'size:8',
                    'fax_2' => 'size:8',
                     'email' => 'email',
                     'location' => 'required',
	        ];
         
      
         $validator = Validator::make($e, $rules);
	 if ($validator->fails())
	  {
	       $this->message = $validator->messages()->all();
	           // return Redirect::action('UserController@authenticationProcess')->with('flash_error', 'Invalid Credential. Please try again');
	  }
       
          return $this->message;
          
    }
    
    public function jsonChoice()
    {
         $country = Country :: select('countryId','countryName')->get();
         return Response::json($country);
    }
    
    public function jsonCurrency()
    {
         $currency = Currency :: select('currencyId','currencyName')->get();
         return Response::json($currency);
    }
    

}