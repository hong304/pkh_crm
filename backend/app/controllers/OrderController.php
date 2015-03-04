<?php

class OrderController extends BaseController {
    
    public function jsonNewOrder()
    {
        $product = Input::get('product');
        $order = Input::get('order');
        $timer = Input::get('timer');
        
        // Create the invoice
        $ci = new InvoiceManipulation($order['invoiceId']);
        $ci->setInvoice($order);
        
        foreach($product as $p)
        {
            $ci->setItem($p['dbid'], $p['code'], $p['unitprice'], $p['unit'], $p['qty'], $p['itemdiscount'], $p['remark'], $p['deleted']);
        }
        $result = $ci->save();
        
        // Update performance log
        $perf = new InvoiceUserPerformance();
        $perf->invoiceId = $result['invoiceNumber'];
        $perf->userid = Auth::user()->id;
        $perf->timestampe = time();
        $perf->start = $timer['start'];
        $perf->submit = $timer['submit'];
        $perf->select_client = $timer['selected_client'];
        $perf->drilldown = json_encode($timer['product']);
        $perf->save();
        
        
        return Response::json($result);
        
    }
    
    public function jsonVoidInvoice()
    {
        $invoiceId = Input::get('invoiceId');
        $i = Invoice::where('invoiceId', $invoiceId)->first();
        $i->invoiceStatus = 99;
        $i->save();
        $i->delete();
        
    }
    public function jsonCheckClient()
    {
       $keyword = Input::get('client_keyword');
       
       
       if($keyword)
       {
           $clientArray = Customer::where('deliveryZone', Session::get('zone'))->with('Zone')
                            ->where('customerName_chi', 'LIKE', '%'.$keyword.'%')
                            ->orwhere('phone_1', 'LIKE', '%'.$keyword.'%')
                            ->get();
       }
       else
       {
           $clientArray = Customer::where('deliveryZone', Session::get('zone'))->with('Zone')->limit(15)->get();
           
       }

       return Response::json($clientArray);       
    }
    
    public function jsonGetPendingInvoice()
    {
        $base = $invoices = Invoice::where('invoiceStatus', 1);
        
        $invoices = Invoice::categorizePendingInvoice(Invoice::getFullInvoice($base, Input::get('zoneId')));
        
        return Response::json($invoices);
    }
    
    public function jsonGetNotification()
    {
        $return = array('pendingapproval'=>'', 'myrejectedinvoices'=>'');
        
        $invoices = Invoice::select(DB::raw('zoneId, invoiceStatus, count(invoiceId) AS counts'))
                            ->wherein('invoiceStatus', ['1', '3'])
                            ->wherein('zoneId', explode(',', Auth::user()->temp_zone))
                            ->groupBy('invoiceStatus', 'zoneId')
                            ->with('zone')
                            ->get(); 
        
        $summary['countInDataMart'] = 0;
        
        foreach($invoices as $invoice)
        {
            $summary[$invoice->invoiceStatus]['breakdown'][$invoice->zoneId] = [
                'zoneId' => $invoice->zoneId,
                'counts' => $invoice->counts,
                'zoneText' => $invoice->zone->zoneName,
            ];
            $summary[$invoice->invoiceStatus]['countInDataMart'] = (isset($summary[$invoice->invoiceStatus]['countInDataMart']) ? $summary[$invoice->invoiceStatus]['countInDataMart'] : 0) + $invoice->counts;
            
            $summary['countInDataMart'] += $invoice->counts;
        }
        
        
        
        
        return Response::json($summary);
    }
    
    public function jsonUpdateInvoiceStatus()
    {
        $invoiceId = Input::get('target');
        $status = Input::get('status');
                
        $ismanager = new InvoiceStatusManager($invoiceId);
        
        if($status == 'Approve')
        {
            $ismanager->approve();
        }
        elseif($status == 'Reject')
        {
            $ismanager->reject();
        }
        
        
    }

    public function jsonQueryFactory()
    {
        $mode = Input::get('mode');
        
        if($mode == 'collection')
        {
            Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);
            
            $filter = Input::get('filterData');
             
            // start with date filter
            switch($filter['deliverydate'])
            {
                case 'today' :
                    $dDateBegin = strtotime("today 00:00");
                    $dDateEnd = strtotime("today 23:59");
                    break;
                case 'coming-7-days' :
                    $dDateBegin = strtotime("today 00:00");
                    $dDateEnd = strtotime("+1 week");
                    break;
                case 'tomorrow' :
                    $dDateBegin = strtotime("tomorrow 00:00");
                    $dDateEnd = strtotime("tomorrow 23:59");
                    break;
                case 'past-7-days' :
                    $dDateBegin = strtotime("7 days ago 00:00");
                    $dDateEnd = strtotime("today 23:59");
                    break;
                case 'past-30-days' :
                    $dDateBegin = strtotime("30 days ago 00:00");
                    $dDateEnd = strtotime("today 23:59");
                    break;
                case 'past-180-days' :
                    $dDateBegin = strtotime("180 days ago 00:00");
                    $dDateEnd = strtotime("today 23:59");
                    break;    
                case 'extensible-180-180'   :
                    $dDateBegin = strtotime("180 days ago 00:00");
                    $dDateEnd = strtotime("+180days 00:00");
                    break;
                default :
                    $dDateBegin = strtotime("today 00:00");
                    $dDateEnd = strtotime("today 23:59");
                    break;
            }
            
            //dd($dDateBegin, $dDateEnd, date("Y-m-d H:i:s", $dDateBegin), date("Y-m-d H:i:s", $dDateEnd));
            
            $invoice = Invoice::where('deliveryDate', '>=', $dDateBegin)->where('deliveryDate', '<=', $dDateEnd);
            
            // zone
            $permittedZone = explode(',', Auth::user()->temp_zone);
            
            if(isset($filter['zone']) && $filter['zone'] != '')
            {
                // check if zone is within permission            
                if(!in_array($filter['zone']['zoneId'], $permittedZone))
                {
                    // *** status code to be updated
                    App::abort(404);
                }
                else
                {
                    $invoice->where('zoneId', $filter['zone']['zoneId']);
                }
            }
            else
            {
                $invoice->wherein('zoneId', $permittedZone);
            }
            
            // status
            if($filter['status'] != '0')
            {
                $invoice->where('invoiceStatus', $filter['status']);
            }
            if($filter['status'] == '99')
            {
                $invoice->withTrashed();
            }
            
            // client id
            if($filter['clientId'] != '0')
            {
                $invoice->where('customerId', $filter['clientId']);
            }
            
            // invoice number
            if($filter['invoiceNumber'] != '')
            {
                $invoice->where('invoiceId', 'LIKE', '%'.$filter['invoiceNumber'].'%');
            }
            
            // created by
            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
            $invoices = $invoice->with('invoiceItem', 'client', 'staff')->orderby('invoiceId', 'desc')->paginate($page_length);
            
            foreach($invoices as $invoice)
            {
                $invoice->link = '<span onclick="viewInvoice(\''.$invoice->invoiceId.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 檢視</span>';
            }
        }
        elseif($mode == 'single')
        {
            $invoices = Invoice::where('invoiceId', Input::get('invoiceId'))
                                ->with('invoiceItem', 'products', 'client', 'staff', 'printqueue', 'audit', 'audit.User')
                                ->withTrashed()
                                ->first();

        }
        //dd(DB::getQueryLog());
        return Response::json($invoices);
        
        
    }
    
    public function jsonGetSingleInvoice() 
    {
        $invoiceId = Input::get('invoiceId'); 
        
        $base = Invoice::where('invoiceId', $invoiceId);
        
        $invoice = Invoice::categorizePendingInvoice(Invoice::getFullInvoice($base));
        //dd($invoice);
        $returnInformation = [
            'invoice' => array_values($invoice['categorized'])[0]['invoices'][0],
            'entrieinfo' => array_values($invoice['categorized'])[0]['zoneName'],
        ];
        return Response::json($returnInformation);
    }
    
    public function jsonGetClientLastInvoice()
    {
        $clientId = Input::get('customerId');
        $lastinv = [];
        
        $invoice = Invoice::select('invoiceId')->where('customerId', $clientId)->orderBy('invoiceId', 'desc')->first();
        
        if($invoice)
        {
            $items = InvoiceItem::select('productId', 'productQtyUnit', 'productQty', 'productPrice')->where('invoiceId', $invoice->invoiceId)->get();
            
            foreach($items as $item)
            {
                
                $lastinv[$item->productId][] = $item->toArray();
            }
        }

        return Response::json($lastinv);
    }
    
    public function jsonUnloadInvoice()
    {
        $invoiceId = Input::get('invoiceId');
        $detail = Input::get('detail');
        
        $unloader = new InvoiceUnloader($invoiceId);
        if($detail['action'] == 'cancel')
        {
            $unloader->cancel();
        }
        elseif($detail['action'] == 'change-deliverydate')
        {
            $unloader->changeDate($detail['newdate']);
        }
    }

}