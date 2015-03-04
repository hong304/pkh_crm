<?php

class CustomerController extends BaseController {

    /*
     * @func jsonCheckClient
     * @parm post string keyword
     * @cache enabled
     * @return json
     */
    
    public function jsonCheckClient()
    {
       # Request
       $time_start = microtime(true);
       $keyword = Input::has('client_keyword') && Input::get('client_keyword') != '' ? Input::get('client_keyword') : 'na';
       
       # Process
       if($keyword != 'na')
       {           
           $keyword = str_replace(array('?', '*'), '%', $keyword);
           $clientArray = Customer::select('customerId', 'customerName_chi', 'address_chi', 'deliveryZone', 'phone_1', 'routePlanningPriority', 'paymentTermId', 'discount')
                                   ->wherein('deliveryZone', explode(',', Auth::user()->temp_zone))
                                   ->where('status', '1')
                                   ->where(function($query) use($keyword)
                                   {
                                       $query->where('customerName_chi', 'LIKE', '%'.$keyword.'%')
                                       ->orwhere('phone_1', 'LIKE', '%'.$keyword.'%')
                                       ->orwhere('customerId', 'LIKE', '%' . $keyword . '%');
                                   })
                                   ->with('Zone')
                                   ->limit(50)
                                   ->get();
           
       }
       else
       {
           $clientArray = Customer::where('deliveryZone', Session::get('zone'))
                                  ->where('status', '1')
                                  ->with('Zone')
                                  ->limit(15)
                                  ->get(); 
     
       }
       $time_end = microtime(true);
       $time = $time_end - $time_start;
       syslog(LOG_INFO, "Search $keyword in $time seconds");
       return Response::json($clientArray);
       
    }
    
    public function jsonFindClientById()
    {
        if(!Input::has('customerId'))
        {
            
        }
        
        $id= Input::get('customerId'); 
        
        $clientArray = Customer::select('customerId', 'customerName_chi', 'address_chi', 'deliveryZone', 'phone_1', 'routePlanningPriority', 'paymentTermId', 'discount')
                                ->where('customerId', $id)->with('Zone')
                                ->first();
        
        return Response::json($clientArray);
    }
    
    public function jsonManiulateCustomer()
    {
        $i = Input::get('customerInfo');
        $cm = new CustomerManipulation($i['customerId']);
        $id = $cm->save($i);
        
        return Response::json(['mode'=>($i['customerId'] == $id ? 'update' : 'create'), 'id'=>$id]);
    }
    
    public function jsonQueryCustomer()
    {
        
        
        $mode = Input::get('mode');
        
        if($mode == 'collection')
        {
            $filter = Input::get('filterData');
            Paginator::setCurrentPage((Input::get('start')+10) / Input::get('length'));
            $customer = Customer::select('*');
            
            // client id
            if($filter['clientId'])
            {
                $customer->where('customerId', $filter['clientId']);
            }
            
            // zone
            $permittedZone = explode(',', Auth::user()->temp_zone);
            
            if($filter['zone'] != '')
            {
                // check if zone is within permission
                if(!in_array($filter['zone']['zoneId'], $permittedZone))
                {
                    // *** status code to be updated
                    App::abort(404);
                }
                else
                {
                    $customer->where('deliveryZone', $filter['zone']['zoneId']);
                }
            }
            else
            {
                $customer->wherein('deliveryZone', $permittedZone);
            }
            
            // query
            
            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
            $customer = $customer->paginate($page_length);
            
            
            foreach($customer as $c)
            {
                if($c->paymentTermId == '1')
                {
                    $c->paymentTerms = 'Cash';
                }
                elseif($c->paymentTermId == '2')
                {
                    $c->paymentTerms = 'Credit';
                }
                else
                {
                    $c->paymentTerms = 'UNKNOWN';
                }
            
                $c->link = '<span onclick="editCustomer(\''.$c->customerId.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
            }
        }
        elseif($mode == 'single')
        {
            $customer = Customer::where('customerId', Input::get('customerId'))->first();
        }
        
        return Response::json($customer);
    }
 

}