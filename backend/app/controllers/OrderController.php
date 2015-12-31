<?php

class OrderController extends BaseController
{

    public function jsonHoliday()
    {
        if(checkdate(12, 31, date("Y")))
            $current_year = date('Y', strtotime('+1 year'));
        else
            $current_year = date("Y");

        $holidays = holiday::where('year', $current_year)->first();

        $h_array = explode(",", $holidays->date);
        foreach ($h_array as &$v) {
            $md = explode("-", $v);
            $m = str_pad($md[0], 2, '0', STR_PAD_LEFT);
            $d = str_pad($md[1], 2, '0', STR_PAD_LEFT);
            $v = $m . '-' . $d;
        }
        return Response::json($h_array);

    }

    public function checkInvoiceIdExist(){

        $customer = Invoice::select('InvoiceId')->where('InvoiceId', Input::get('invoiceId'))->first();
        $customer = count($customer);
        return Response::json($customer);
    }

    public function jsonNewOrder()
    {



        $itemIds = [];

        $product = Input::get('product');


        $order = Input::get('order');
     //   $timer = Input::get('timer');

       /* if(isset($order['invoiceNumber']) and $order['invoiceId'] == ''){

            $invoiceId = invoice::where('invoiceId',$order['invoiceNumber'])->first();
            if($invoiceId === null){

            }else{
                return [
                    'result' => false,
                    'status' => 0,
                    'invoiceNumber' => $order['invoiceNumber'],
                    'invoiceItemIds' => 0,
                    'message' => 'Invoice no. exist, please re-enter!',
                ];
            }
        }*/



        // Create the invoice
        $ci = new InvoiceManipulation($order['invoiceId']);
        $ci->setInvoice($order);


        $have_item = false;
        $ii=0;
        $j=0;


        foreach ($product as $k => &$p) {
            if($p['productLocation'] == ''){
                unset($product[$k]);
            }else{

                if ($p['dbid'] != '' && $p['deleted'] == 0 && $p['qty'] != 0)
                    $itemIds[] = $p['dbid'];

                if ($p['dbid'] == '' && $p['code'] != '' && $p['deleted'] == 0 && $p['qty'] != 0){
                    $have_item = true;
                }

                if ($p['deleted'] == 0 && $p['qty'] > 0)
                    $ii++;


                if ($p['deleted'] == 0 && $p['qty'] < 0)
                    $j++;

            }
        }


        if($order['status'] == 98){
            if($ii > 0){
                return [
                    'result' => false,
                    'status' => 0,
                    'invoiceNumber' => '',
                    'invoiceItemIds' => 0,
                    'message' => '退貨單不能有正數貨品',
                ];
            }
        }


        if($order['status'] == 97){
            if($ii < 1 || $j < 1){
                return [
                    'result' => false,
                    'status' => 0,
                    'invoiceNumber' => '',
                    'invoiceItemIds' => 0,
                    'message' => '此單未完成',
                ];
            }
        }

        if ($order['invoiceId'] != '') {

            if (count($itemIds) == 0 && !$have_item)
                return [
                    'result' => false,
                    'status' => 0,
                    'invoiceNumber' => $order['invoiceId'],
                    'invoiceItemIds' => 0,
                    'message' => '未有下單貨品(Error:001)',
                ];
            else if (count($itemIds) == 0){
                $i = InvoiceItem::where('invoiceId', $order['invoiceId'])->with('productDetail');
                $deletedItemFromDB = $i->get();
                $i->delete();
            }else{
                $i = InvoiceItem::whereNotIn('invoiceItemId', $itemIds)->where('invoiceId', $order['invoiceId'])->with('productDetail');
                $deletedItemFromDB = $i->get();
                $i->delete();
            }

            $this->backToStock($deletedItemFromDB);

            foreach ($deletedItemFromDB as $v) {
                $sql = "SELECT * FROM Invoice i LEFT JOIN InvoiceItem ii ON i.invoiceId=ii.invoiceId WHERE invoiceStatus not in ('98','96','99','97') and ii.created_at != '' and ii.deleted_at is null and customerId = '" . $order['clientId'] . "' AND ii.productId = '" . $v->productId . "' order by ii.updated_at desc limit 1";
                $item = DB::select(DB::raw($sql));

                if (count($item) > 0) {
                    $lastitem = lastitem::where('customerId', $order['clientId'])->where('productId', $item[0]->productId)->first();
                    $lastitem->unit_level = $item[0]->productQtyUnit;
                    $lastitem->unit_text = $item[0]->productUnitName;
                    $lastitem->price = $item[0]->productPrice;
                    $lastitem->qty = $item[0]->productQty;
                    $lastitem->discount = $item[0]->productDiscount;
                    $lastitem->deliveryDate = date('Y-m-d',$item[0]->deliveryDate);
                    $lastitem->updated_at = $item[0]->updated_at;
                    $lastitem->save();
                } else
                    lastitem::where('customerId', $order['clientId'])->where('productId', $v->productId)->delete();

            }

        } else {
            if (!$have_item)
                return [
                    'result' => false,
                    'status' => 0,
                    'invoiceNumber' => 0,
                    'invoiceItemIds' => 0,
                    'message' => '未有下單貨品(Error:002)',
                ];
        }



      foreach ($product as $p) {

            if($p['qty'] == 0)
                return [
                    'result' => false,
                    'status' => 0,
                    'invoiceNumber' => $order['invoiceId'],
                    'invoiceItemIds' => 0,
                    'message' => $p['code'].':貨品數量不能等於零',
                ];


             if ($p['deleted'] == 0 && $p['qty'] != 0){

                 $dirty = false;

                     if ($p['dbid']) {

                         $item = InvoiceItem::where('invoiceItemId', $p['dbid'])->first();

                         $item->productId = $p['code'];
                         $item->productUnitName = trim($p['unit']['label']);
                         $item->productQty = $p['qty'];

                         if ($item->isDirty()) {

                             foreach ($item->getDirty() as $attribute => $value) {
                                 if (!in_array($attribute, array('backgroundcode'))) {

                                   if ($order['status'] != '96' && $order['status'] != '97') {
                                         $invoiceitembatchs = invoiceitemBatch::where('invoiceItemId', $item->getOriginal('invoiceItemId'))->where('productId', $item->getOriginal('productId'))->get();
                                         if (count($invoiceitembatchs) > 0)
                                             foreach ($invoiceitembatchs as $k1 => $v1) {
                                                 $receivings = Receiving::where('productId', $v1->productId)->where('receivingId', $v1->receivingId)->first();
                                                 $receivings->good_qty += $v1->unit;
                                                 $receivings->save();
                                                 $v1->delete();
                                             }
                                     }

                                     $dirty = true;
                                 }
                             }
                         }
                     }


                     if ($dirty || !$p['dbid']) {
                         $receivings = Receiving::where('productId', $p['code'])->where('good_qty', '>=', $this->normalizedUnit($p))->first();
                         if (count($receivings) == null) {
                             return [
                                 'result' => false,
                                 'status' => 0,
                                 'invoiceNumber' => '',
                                 'invoiceItemIds' => 0,
                                 'message' => $p['code'] . '沒有足夠的貨量',
                             ];
                         }
                     }

             }



        }




        foreach ($product as $p) {
            /*  if($p!=0){

                  $overflow = false;
                  $getQty = invoiceitem::leftJoin('invoice','invoice.invoiceId','=','invoiceitem.invoiceId')->leftJoin('customer','invoice.customerId','=','customer.customerId')->where('invoice.customerId',$order['clientId'])->where('deliveryDate',strtotime($order['deliveryDate']))->where('productId',$p['code'])->get();//sum('productQty');
                  $maxQty = product::select('maxSellingQty')->where('productId',$p['code'])->first();
  pd('s');
                  $carton = ($maxQty['productPacking_carton'] == false) ? 1:$maxQty['productPacking_carton'];
                  $inner = ($maxQty['productPacking_inner']==false) ? 1:$maxQty['productPacking_inner'];
                  $unit = ($maxQty['productPacking_unit'] == false) ? 1 : $maxQty['productPacking_unit'];

                  if ($p['unit'] == 'carton') {
                      if($p['qty']*$inner*$unit > $maxQty['maxSellingQty']-$getQty)
                          $overflow = true;
                  }

                  if ($p['unit'] == 'inner') {
                      if($carton*$p['qty']*$unit > $maxQty['maxSellingQty']-$getQty)
                          $overflow = true;
                  }

                  if ($p['unit'] == 'unit') {
                      if($carton*$inner*$p['qty'] > $maxQty['maxSellingQty']-$getQty)
                          $overflow = true;
                  }

                  if($overflow)
                      return [
                          'result' => false,
                          'status' => 0,
                          'invoiceNumber' => 0,
                          'invoiceItemIds' => 0,
                          'message' => $p['code'].'超過每日下單數量,限制為:'.$maxQty['maxSellingQty'],
                      ];

                  if(!$overflow)
                      pd($getQty);
              } */


            $ci->setItem($p['dbid'], $p['code'], $p['unitprice'], $p['unit'], $p['productLocation'], $p['qty'], $p['itemdiscount'], $p['remark'], $p['deleted'],$p['productPacking']);
        }
        $result = $ci->save();

        // Update performance log
        /*    $perf = new InvoiceUserPerformance();
            $perf->invoiceId = $result['invoiceNumber'];
            $perf->userid = Auth::user()->id;
            $perf->timestampe = time();
            $perf->start = $timer['start'];
            $perf->submit = $timer['submit'];
            $perf->select_client = $timer['selected_client'];
            $perf->drilldown = json_encode($timer['product']);
            $perf->save();*/


        return Response::json($result);

    }

    public function normalizedUnit($i){

        $inner = ($i['productPacking']['inner']) ? $i['productPacking']['inner']:1;
        $unit = ($i['productPacking']['unit']) ? $i['productPacking']['unit']:1;

            if($i['unit']['value'] == 'carton')
                $real_normalized_unit =  $i['qty']*$inner*$unit;
            else if($i['unit']['value'] == 'inner')
                $real_normalized_unit =   $i['qty']*$unit;
            else
                $real_normalized_unit =  $i['qty'];

        return $real_normalized_unit;
    }

    public function packingSize($i){

        $inner = ($i['productPacking']['inner']) ? $i['productPacking']['inner']:1;
        $unit = ($i['productPacking']['unit']) ? $i['productPacking']['unit']:1;


            if($i['unit']['value'] == 'carton')
                $real_normalized_unit =  $inner*$unit;
            else if($i['unit']['value'] == 'inner')
                $real_normalized_unit =   $unit;
            else
                $real_normalized_unit =  $i['qty'];

        return $real_normalized_unit;

    }

    public function jsonVoidInvoice()
    {

        $invoiceId = Input::get('invoiceId');

        $i = Invoice::where('invoiceId', $invoiceId)->with('invoiceitem')->first();
        $i->previous_status = $i->invoiceStatus;
        $i->invoiceStatus = 99;
        $i->save();
        $i->delete();

        invoiceitem::where('invoiceId', $invoiceId)->update(['itemStatus'=>99]);
        invoiceitem::where('invoiceId', $invoiceId)->delete();

        $this->backToStock($i->invoiceitem);

        foreach ($i->invoiceitem as $v) {
            $sql = "SELECT * FROM Invoice i LEFT JOIN InvoiceItem ii ON i.invoiceId=ii.invoiceId WHERE invoiceStatus not in ('98','96','99','97') and ii.created_at != '' and ii.deleted_at is null and customerId = '" . $i->customerId . "' AND ii.productId = '" . $v->productId . "' order by ii.updated_at desc limit 1";
            $item = DB::select(DB::raw($sql));

            if (count($item) > 0) {
                $lastitem = lastitem::where('customerId', $i->customerId)->where('productId', $item[0]->productId)->first();
                $lastitem->unit_level = $item[0]->productQtyUnit;
                $lastitem->unit_text = $item[0]->productUnitName;
                $lastitem->price = $item[0]->productPrice;
                $lastitem->qty = $item[0]->productQty;
                $lastitem->discount = $item[0]->productDiscount;
                $lastitem->updated_at = $item[0]->updated_at;
                $lastitem->deliveryDate = date('Y-m-d',$item[0]->deliveryDate);
                $lastitem->save();
            } else
                lastitem::where('customerId', $i->customerId)->where('productId', $v->productId)->delete();

        }


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
        $invoices = Invoice::select(DB::raw('zoneId, invoiceStatus, count(invoiceId) AS counts'), 'deliveryDate')
            ->wherein('invoiceStatus', ['1', '3'])
            ->wherein('zoneId', explode(',', Auth::user()->temp_zone))
            ->where('deliveryDate', '>=', strtotime("today 00:00"))
            ->groupBy('invoiceStatus', 'zoneId')
            ->with('zone')
            ->get();

        foreach ($invoices as $invoice) {
            $summary[$invoice->invoiceStatus . 'today']['breakdown'][$invoice->zoneId] = [
                'zoneId' => $invoice->zoneId,
                'counts' => $invoice->counts,
                'zoneText' => $invoice->zone->zoneName,
            ];
            $summary[$invoice->invoiceStatus . 'today']['countInDataMart'] = (isset($summary[$invoice->invoiceStatus . 'today']['countInDataMart']) ? $summary[$invoice->invoiceStatus . 'today']['countInDataMart'] : 0) + $invoice->counts;
        }


        $invoices = Invoice::select(DB::raw('zoneId, invoiceStatus, count(invoiceId) AS counts'), 'deliveryDate')
            ->wherein('invoiceStatus', ['1', '3'])
            ->wherein('zoneId', explode(',', Auth::user()->temp_zone))
            ->where('deliveryDate', '<', strtotime("today 00:00"))
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
                    ->orwhere('Invoice.invoiceStatus', '97')
                    ->orwhere('Invoice.invoiceStatus', '96')
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
            } else {
                if (isset($filter['deliverydate1']))
                    if ($filter['deliverydate1'] == 'today') {
                        $invoice = Invoice::select('*')->where('deliveryDate', '>=', strtotime("today 00:00"));
                    } elseif ($filter['deliverydate1'] == 'yesterday') {
                        $invoice = Invoice::select('*')->where('deliveryDate', '<', strtotime("today 00:00"));
                    } else
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

           if ($filter['status'] == '101') {
               $invoice->withTrashed();
           }else{
                if ($filter['status'] == '100') {
                    $invoice->where(function ($query) {
                        $query->where('previous_status', 1)->where('invoiceStatus', 2);
                    });
                }else if ($filter['status'] != '0') {
                    $invoice->where('invoiceStatus', $filter['status']);
                }
                if ($filter['status']=='99'){
                    $invoice->withTrashed();
                }
           }
            // client id
            if ($filter['clientId'] != '0') {
                $invoice->where('customerId', $filter['clientId']);
            }

            if($filter['staffName'])
                $invoices = $invoice->with('client','laststaff')->whereHas('laststaff', function($q) use($filter)
                {
                    $q->where('name',$filter['staffName']);

                })->orderby('invoiceId', 'desc');
            else
                $invoices = $invoice->with('client', 'laststaff')->orderby('invoiceId', 'desc');

            return Datatables::of($invoices)
                ->addColumn('link', function ($invoice) {
                    return '<span onclick="viewInvoice(\'' . $invoice->invoiceId . '\',\''.$invoice->invoiceStatus.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 檢視</span>';
                })->addColumn('id', function ($invoice) {
                    return '<a onclick="goEdit(\'' . $invoice->invoiceId . '\')">' . $invoice->invoiceId . '</a>';
                })->setRowClass(function ($invoice) {
                    return $invoice->invoiceStatus == 99 ? 'del-row' : '';
                })
                ->make(true);

        } elseif ($mode == 'single') {


            $invoices = Invoice::where('invoiceId', Input::get('invoiceId'))
                ->with(['invoiceItem' => function ($query) use ($ids) {
                    $query->orderBy('productLocation', 'asc')->orderBy('productQtyUnit', 'asc')->orderByRaw(DB::raw("FIELD(productUnitName, $ids) DESC"))->orderBy('productId', 'asc')->with('productDetail');
                if(Input::get('invoiceStatus') == '99')
                        $query->where('itemStatus',99)->withTrashed();
                }])->with('client', 'staff', 'printqueue', 'audit', 'audit.User')
                ->withTrashed()
                ->first();

        }
   // pd(DB::getQueryLog());

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
            'workingDay' => SystemController::getPreviousDay(5),
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
        // $productId = Input::get('productId');
        //  $sql = "SELECT * FROM Invoice i LEFT JOIN InvoiceItem ii ON i.invoiceId=ii.invoiceId WHERE invoiceStatus not in ('98','96','99') and ii.created_at != '' and customerId = '" . $customerId . "' AND ii.productId = '" . $productId . "' order by ii.updated_at desc";
        //  $items = DB::select(DB::raw($sql));


        $products = lastitem::where('customerId', $customerId)->get()->toArray();
        if (count($products) == 0) {
            $products = [];
            return Response::json($products);
        } else {
            $products = Product::compileProductStandardForm($products);
            return Response::json($products);
        }
    }


    public function jsonGetSameDayOrder()
    {
        $customerId = Input::get('customerId');
        $deliveryDate = Input::get('deliveryDate');

        $deliveryDate = strtotime($deliveryDate);

        $invoice_id = Invoice::where('customerId', $customerId)->where('deliveryDate', $deliveryDate)->whereIn('invoiceStatus',[1,2])->orderBy('invoiceId', 'desc')->first();

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

    public function getNoOfOweInvoices(){
        $customerId = Input::get('customerId');
        $count = Invoice::where('customerId',$customerId)->where('invoiceStatus',20)->where('paymentTerms',1)->count();

        $unlock = Customer::select('unlock')->where('customerId',$customerId)->first();
        if($unlock->unlock)
            return Response::json(3);
        else
            return Response::json($count);
    }

    public function backToStock($invoiceItems){
        foreach($invoiceItems as $k => $v){
                $invoiceitembatchs = invoiceitemBatch::where('invoiceItemId',$v->invoiceItemId)->where('productId',$v->productId)->get();
                foreach($invoiceitembatchs as $k1 => $v1){
                    $receivings = Receiving::where('productId',$v1->productId)->where('receivingId',$v1->receivingId)->first();
                    $receivings->good_qty += $v1->unit;
                    $receivings->save();
                    $v1->delete();
                }
        }
    }
}