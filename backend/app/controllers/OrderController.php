<?php

class OrderController extends BaseController
{

    public function jsonHoliday(){
       $holidays =  holiday::where('year',date("Y"))->first();

            $h_array = explode(",", $holidays->date);
        foreach($h_array as &$v){
            $md = explode("-",$v);
            $m = str_pad($md[0], 2, '0', STR_PAD_LEFT);
            $d = str_pad($md[1], 2, '0', STR_PAD_LEFT);
            $v = $m.'-'.$d;
        }
        return Response::json($h_array);

    }
    public function jsonNewOrder()
    {
        $itemIds = [];
        $product = Input::get('product');
        $order = Input::get('order');
        $timer = Input::get('timer');
        //  pd($order);
        // Create the invoice
        $ci = new InvoiceManipulation($order['invoiceId']);
        $ci->setInvoice($order);

      // pd($product);
        $have_item=false;
        foreach ($product as $p) {
            if ($p['dbid'] != '' && $p['deleted'] == 0 && $p['qty']>0)
                $itemIds[] = $p['dbid'];
            if ($p['dbid'] == '' && $p['code'] != '')
                $have_item = true;
        }

        if ($order['invoiceId'] != '') {
            if (count($itemIds) == 0 && !$have_item)
                return [
                    'result' => false,
                    'status' => 0,
                    'invoiceNumber' => 0,
                    'invoiceItemIds' => 0,
                    'message' => '未有下單貨品',
                ];
            else if(count($itemIds) == 0)
                InvoiceItem::where('invoiceId', $order['invoiceId'])->delete();
            else
                InvoiceItem::whereNotIn('invoiceItemId', $itemIds)->where('invoiceId', $order['invoiceId'])->delete();

        }
        foreach ($product as $p) {
            $ci->setItem($p['dbid'], $p['code'], $p['unitprice'], $p['unit'], $p['productLocation'], $p['qty'], $p['itemdiscount'], $p['remark'], $p['deleted']);
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
        $i->previous_status = $i->invoiceStatus;
        $i->invoiceStatus = 99;
        $i->save();
        $i->delete();

    }

    public function jsonCheckClient()
    {
        $keyword = Input::get('client_keyword');


        if ($keyword) {
            $clientArray = Customer::where('deliveryZone', Session::get('zone'))->with('Zone')
                ->where('customerName_chi', 'LIKE', '%' . $keyword . '%')
                ->orwhere('phone_1', 'LIKE', '%' . $keyword . '%')
                ->get();
        } else {
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
           $invoices = Invoice::select(DB::raw('zoneId, invoiceStatus, count(invoiceId) AS counts'),'deliveryDate')
            ->wherein('invoiceStatus', ['1', '3'])
            ->wherein('zoneId', explode(',', Auth::user()->temp_zone))
            ->where('deliveryDate','>=',strtotime("today 00:00"))
            ->groupBy('invoiceStatus', 'zoneId')
            ->with('zone')
            ->get();

     //   $summary['countInDataMart'] = 0;



        foreach ($invoices as $invoice) {
                $summary[$invoice->invoiceStatus.'today']['breakdown'][$invoice->zoneId] = [
                    'zoneId' => $invoice->zoneId,
                    'counts' => $invoice->counts,
                    'zoneText' => $invoice->zone->zoneName,
                ];
                $summary[$invoice->invoiceStatus.'today']['countInDataMart'] = (isset($summary[$invoice->invoiceStatus.'today']['countInDataMart']) ? $summary[$invoice->invoiceStatus.'today']['countInDataMart'] : 0) + $invoice->counts;
            }




        $invoices = Invoice::select(DB::raw('zoneId, invoiceStatus, count(invoiceId) AS counts'),'deliveryDate')
            ->wherein('invoiceStatus', ['1', '3'])
            ->wherein('zoneId', explode(',', Auth::user()->temp_zone))
            ->where('deliveryDate','<',strtotime("today 00:00"))
            ->groupBy('invoiceStatus', 'zoneId')
            ->with('zone')
            ->get();

        foreach ($invoices as $invoice) {
            $summary[$invoice->invoiceStatus . 'yesterday']['breakdown'][$invoice->zoneId] = [
                'zoneId' => $invoice->zoneId,
                'counts' => $invoice->counts,
                'zoneText' => $invoice->zone->zoneName,
            ];
            $summary[$invoice->invoiceStatus . 'yesterday']['countInDataMart'] = (isset($summary[$invoice->invoiceStatus . 'yesterday']['countInDataMart']) ? $summary[$invoice->invoiceStatus . 'yesterday']['countInDataMart'] : 0) + $invoice->counts;
        }

        $invoices = Invoice::select(DB::raw('zoneId, invoiceStatus, count(invoiceId) AS counts'))
            ->where('invoiceStatus', '3')
            ->wherein('zoneId', explode(',', Auth::user()->temp_zone))
            ->groupBy('invoiceStatus', 'zoneId')
            ->with('zone')
            ->get();

        foreach ($invoices as $invoice) {
            $summary[$invoice->invoiceStatus]['breakdown'][$invoice->zoneId] = [
                'zoneId' => $invoice->zoneId,
                'counts' => $invoice->counts,
                'zoneText' => $invoice->zone->zoneName,
            ];
            $summary[$invoice->invoiceStatus]['countInDataMart'] = (isset($summary[$invoice->invoiceStatus]['countInDataMart']) ? $summary[$invoice->invoiceStatus]['countInDataMart'] : 0) + $invoice->counts;
        }


        $jobscount = PrintQueue::wherein('target_path', explode(',', Auth::user()->temp_zone))->whereIn('status', ['queued', 'fast-track'])
            ->where('insert_time', '>', strtotime("3 days ago"))
            ->leftJoin('Invoice', function ($join) {
                $join->on('PrintQueue.invoiceId', '=', 'Invoice.invoiceId');
            })
            ->where(function ($query) {
                $query->where('Invoice.invoiceStatus', '2')
                    //->orwhere('Invoice.invoiceStatus','4')
                    ->orwhere('Invoice.invoiceStatus', '97')
                    ->orwhere('Invoice.invoiceStatus', '98');
            })->count();

      //  $summary['open'] = Invoice::where('invoiceStatus', 2)->wherein('zoneId', explode(',', Auth::user()->temp_zone))->count();

        $summary['printjobs'] = $jobscount;
        $summary['logintime'] = Session::get('logintime');
        $summary['db_logintime'] = Auth::user()->logintime;



        return Response::json($summary);
    }

    public function jsonUpdateInvoiceStatus()
    {
        $invoiceId = Input::get('target');
        $status = Input::get('status');

        $ismanager = new InvoiceStatusManager($invoiceId);

        if ($status == 'Approve') {
            $ismanager->approve();
        } elseif ($status == 'Reject') {
            $ismanager->reject();
        } elseif ($status == 'Restore') {
            $ismanager->Restore();
        }


    }

    public function jsonQueryFactory()
    {
        $itemIds = array('桶', '排', '扎', '箱');

        $ids = "'" . implode("','", $itemIds) . "'";

        $mode = Input::get('mode');

        if ($mode == 'collection') {
            Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);

            $filter = Input::get('filterData');

            $dDateBegin = strtotime($filter['deliverydate']);
            $dDateEnd = strtotime($filter['deliverydate2']);
            //dd($dDateBegin, $dDateEnd, date("Y-m-d H:i:s", $dDateBegin), date("Y-m-d H:i:s", $dDateEnd));

          /*  if(isset($filter['deliverydate1']))
                $invoice = Invoice::select('*');
            else*/
            $invoice = Invoice::where('deliveryDate', '>=', $dDateBegin)->where('deliveryDate', '<=', $dDateEnd);

            // invoice number
            if ($filter['invoiceNumber'] != '') {
                $invoice = Invoice::select('*');
                $invoice->where('invoiceId', 'LIKE', '%' . $filter['invoiceNumber'] . '%');
            }else{
                if(isset($filter['deliverydate1']))
                    if($filter['deliverydate1'] == 'today'){
                        $invoice = Invoice::select('*')->where('deliveryDate','>=',strtotime("today 00:00"));
                    }elseif($filter['deliverydate1'] == 'yesterday'){
                        $invoice = Invoice::select('*')->where('deliveryDate','<',strtotime("today 00:00"));
                    }else
                        $invoice = Invoice::select('*');
                else
                     $invoice = Invoice::where('deliveryDate', '>=', $dDateBegin)->where('deliveryDate', '<=', $dDateEnd);
            }

            // zone
            $permittedZone = explode(',', Auth::user()->temp_zone);

            if (isset($filter['zone']) && $filter['zone'] != '') {
                // check if zone is within permission            
                if (!in_array($filter['zone']['zoneId'], $permittedZone)) {
                    // *** status code to be updated
                    App::abort(404);
                } else {
                    $invoice->where('zoneId', $filter['zone']['zoneId']);
                }
            } else {

                //  pd($permittedZone);

                $invoice->wherein('zoneId', $permittedZone);
            }

            // status
            if ($filter['status'] == '100') {
                $invoice->where(function ($query){
                    $query->where('previous_status', 1)->where('invoiceStatus', 2);
                });
            }else if ($filter['status'] != '0') {
                $invoice->where('invoiceStatus', $filter['status']);
            }

            if ($filter['status'] == '99') {
                $invoice->withTrashed();
            }

            // client id
            if ($filter['clientId'] != '0') {
                $invoice->where('customerId', $filter['clientId']);
            }



            // created by
            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;


            /*  $invoices = $invoices->with(['invoiceItem'=>function($query) use($ids){
                  $query->orderByRaw(DB::raw("FIELD(productUnitName, $ids) DESC"));
              }])->with( 'client', 'staff')->get();*/


            $invoices = $invoice->with('client', 'laststaff')->orderby('invoiceId', 'desc')->paginate($page_length);

            foreach ($invoices as $invoice) {
                $invoice->link = '<span onclick="viewInvoice(\'' . $invoice->invoiceId . '\')" class="btn btn-xs default"><i class="fa fa-search"></i> 檢視</span>';
                $invoice->id = '<a onclick="goEdit(\'' . $invoice->invoiceId . '\')">'.$invoice->invoiceId.'</a>';
            }
        } elseif ($mode == 'single') {
            $invoices = Invoice::where('invoiceId', Input::get('invoiceId'))
                ->with(['invoiceItem' => function ($query) use ($ids) {
                    $query->orderBy('productLocation','asc')->orderBy('productQtyUnit','asc')->orderByRaw(DB::raw("FIELD(productUnitName, $ids) DESC"))->orderBy('productId','asc');
                }])->with('products', 'client', 'staff', 'printqueue', 'audit', 'audit.User')
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

        if ($invoice) {
            $items = InvoiceItem::select('productId', 'productQtyUnit', 'productQty', 'productPrice')->where('invoiceId', $invoice->invoiceId)->get();

            foreach ($items as $item) {
                $lastinv[$item->productId][] = $item->toArray();
            }
        }

        return Response::json($lastinv);
    }

    public function jsonGetLastItem()
    {

        $customerId = Input::get('customerId');
        $productId = Input::get('productId');
      //  $sql = "SELECT * FROM Invoice i LEFT JOIN InvoiceItem ii ON i.invoiceId=ii.invoiceId WHERE invoiceStatus not in ('98','96','99') and ii.created_at != '' and customerId = '" . $customerId . "' AND ii.productId = '" . $productId . "' order by ii.updated_at desc";
      //  $items = DB::select(DB::raw($sql));
        $items = lastitem::where('customerId',$customerId)->where('productId',$productId)->first();
        if ($items == null)
            return Response::json($items);

        return Response::json($items);
    }

    public function jsonGetSameDayOrder()
    {
        $customerId = Input::get('customerId');
        $deliveryDate = Input::get('deliveryDate');

        $deliveryDate = strtotime($deliveryDate);

        $invoice_id = Invoice::where('customerId', $customerId)->where('deliveryDate', $deliveryDate)->orderBy('invoiceId','desc')->first();

        // pd($invoice_id);

        return Response::json($invoice_id);
    }

    public function jsonUnloadInvoice()
    {
        $invoiceId = Input::get('invoiceId');
        $detail = Input::get('detail');

        $unloader = new InvoiceUnloader($invoiceId);
        if ($detail['action'] == 'cancel') {
            $unloader->cancel();
        } elseif ($detail['action'] == 'backToNormal') {
            $unloader->backToNormal();
            Invoice::where('invoiceId', $invoiceId)->update(['f9_picking_dl' => 0]);
        } elseif ($detail['action'] == 'change-deliverydate') {
            $unloader->changeDate($detail['newdate']);
        }
    }

}