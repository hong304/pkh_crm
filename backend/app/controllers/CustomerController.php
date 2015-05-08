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
      // $time_start = microtime(true);
       $keyword = Input::has('client_keyword') && Input::get('client_keyword') != '' ? Input::get('client_keyword') : 'na';




       # Process
       if($keyword != 'na')
       {


           if(!isset($keyword['keyword']))
               $keyword['keyword'] = '';
           if(!isset($keyword['id']))
               $keyword['id'] = '';
           if(!isset($keyword['zone']['zoneId']))
               $keyword['zone']['zoneId'] = '';

          // $keyword = str_replace(array('?', '*'), '%', $keyword);
           $clientArray = Customer::select('*')

                                   ->where('status', '1')
                                   ->where(function($query) use($keyword)
                                   {
                                       if($keyword['keyword'] !='' && $keyword['id'] !='') {
                                           $query->where('customerName_chi', 'LIKE', '%'.$keyword['keyword'].'%')
                                               ->orwhere('phone_1', 'LIKE', '%'.$keyword['keyword'].'%')
                                               ->where('customerId', 'LIKE', '%' . $keyword['id'] . '%');
                                       }
                                       if($keyword['keyword']!=''){
                                             $query->where('customerName_chi', 'LIKE', '%'.$keyword['keyword'].'%')
                                             ->orwhere('phone_1', 'LIKE', '%'.$keyword['keyword'].'%');
                                             }

                                       if($keyword['id']!='') {
                                           $query->where('customerId', 'LIKE', '%' . $keyword['id'] . '%');
                                       }

                                   });

           if($keyword['zone']['zoneId'] != ''){
               $clientArray->where('deliveryZone',$keyword['zone']['zoneId']);
           }else{
               $clientArray->wherein('deliveryZone',explode(',', Auth::user()->temp_zone));
           }

           $clientArray = $clientArray->with('Zone')
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
      // $time_end = microtime(true);
      // $time = $time_end - $time_start;
      // syslog(LOG_INFO, "Search $keyword in $time seconds");
       return Response::json($clientArray);
       
    }
    
    public function jsonFindClientById()
    {
        if(!Input::has('customerId'))
        {
            
        }
        
        $id= Input::get('customerId'); 
        
        $clientArray = Customer::select('customerId', 'customerName_chi', 'address_chi','remark', 'deliveryZone', 'phone_1', 'routePlanningPriority', 'paymentTermId', 'discount')
                                ->where('customerId', $id)->with('Zone')
                                ->first();
        
        return Response::json($clientArray);
    }
    
    public function jsonManiulateCustomer()
    {

        if(Input::get('mode') == 'del'){
           // pd(Input::get('customer_id'));
           Customer::where('customerId',Input::get('customer_id'))->update(['status'=>2,'deleted'=>1]);
           // p(Input::get('customer_id'));
            return [];
        }

        $i = Input::get('customerInfo');
        $cm = new CustomerManipulation($i['customerId'], (isset($i['productnewId']) ? $i['productnewId']: false));
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
            $customer = Customer::select('*')->where('deleted',false);
            
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

                if($c->status == '1')
                    $c->status = '正常';
                else
                    $c->status = '暫停';
                $c->delete = '<span onclick="delCustomer(\''.$c->customerId.'\')" class="btn btn-xs default"><i class="fa glyphicon glyphicon-remove"></i> 刪除</span>';
                $c->link = '<span onclick="editCustomer(\''.$c->customerId.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
            }
        }
        elseif($mode == 'single')
        {
            $customer = Customer::where('customerId', Input::get('customerId'))->first();
        }elseif($mode == 'checkId'){
            $customer = Customer::select('customerId')->where('customerId', Input::get('customerId'))->first();
            $customer = count($customer);
        }
        
        return Response::json($customer);
    }
 

}