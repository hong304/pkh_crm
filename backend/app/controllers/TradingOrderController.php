<?php

class TradingOrderController extends BaseController
{

    public function jsonHoliday()
    {
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

    public function placeTradingOrder()
    {





        $product = Input::get('product');
        $order = Input::get('order');
        $this->trade_way = ($order['tradingCompany']=='PKH')?1:2;



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

        if($order['invoiceId']!='')
            $this->action = 'update';
        else
            $this->action = 'create';

        if ($this->action == 'create') {
            if($this->trade_way == 1)
                $this->im = new Invoice();
            else
                $this->im = new invoiceTrading();


        } elseif ($this->action == 'update') {
            // check if this invoice exists
            $this->im = Invoice::where('invoiceId', $order['invoiceId'])->firstOrFail();
            if ($this->im->invoiceStatus == 99) {
                $this->status = false;
                $this->message = "This invoice has been suspended and logically forbid to edit. This request has been recorded in audit log.";
            }
            $this->invoiceId = $order['invoiceId'];
        }



        $orderrules = [
            'clientId' => ['required', 'exists:Customer,customerId'],
            'invoiceDate' => ['required'],
            'deliveryDate' => ['required'],
            'dueDate' => ['required'],
            'status' => [''],
            'referenceNumber' => [''],
            'zoneId' => ['numeric'],
            'route' => ['numeric'],
            'address' => ['required'],
        ];

        $orderValidation = Validator::make($order, $orderrules);
        if ($orderValidation->fails()) {
            // if invoice is problematic, kill the user
            $this->status = false;
            $this->message = $orderValidation->messages()->first();
        }

        $this->temp_invoice_information = $order;

        unset($product[0]);


        foreach($product as $p){
            $this->products[] = $p['code'];
        };


        $raw = Product::wherein('productId', $this->products)->get();
        $products = [];
        foreach ($raw as $p) {
            $products[$p->productId] = $p;
        }

        foreach ($product as $p) {

            $product = $products[$p['code']];

            $this->items[] = [
                'dbid' => $p['dbid'],
                'productId' => $p['code'],
                'productPrice' => $p['unitprice'],
                'productQtyUnit' => $p['unit'],
                'productLocation' => $p['productLocation'],
                'productQty' => $p['qty'],
                'productDiscount' => $p['itemdiscount'],
                'productRemark' => $p['remark'],
                'deleted' => $p['deleted'],
                'productUnitName' =>$p['unitName'],
                'productStandardPrice' => $product['productStdPrice_' . strtolower($p['unit'])],
            ];

        }

        $this->__prepareInvoices();
        $this->saveInvoice();



        foreach ($this->items as $i) {

            if ($i['dbid']) {
                if($this->trade_way == 1)
                    $item = InvoiceItem::where('invoiceItemId', $i['dbid'])->first();
                else
                    $item = invoiceitemTrading::where('invoiceItemId', $i['dbid'])->first();



            } else {
                if($this->trade_way == 1)
                    $item = new InvoiceItem();
                else
                    $item = new invoiceitemTrading();

            }



            $item->invoiceId = $this->invoiceId;
            $item->productId = $i['productId'];
            $item->productQtyUnit = $i['productQtyUnit'];
            $item->productLocation = $i['productLocation'];
            $item->productQty = $i['productQty'];
            $item->productPrice = $i['productPrice'];
            $item->productDiscount = $i['productDiscount'];
            $item->productRemark = $i['productRemark'];
            $item->productStandardPrice = $i['productStandardPrice'];
            $item->productUnitName = trim($i['productUnitName']);
            $item->approvedSupervisorId = '27';

            if ($i['dbid']) { //dirty check for qty and price changed
                if ($item->isDirty()) {
                    foreach ($item->getDirty() as $attribute => $value) {
                        if (!in_array($attribute, array('backgroundcode'))) {
                            $item->delete();
                            if($this->trade_way == 1)
                                $item = new InvoiceItem();
                            else
                                $item = new invoiceitemTrading();

                            $item->invoiceId = $this->invoiceId;
                            $item->productId = $i['productId'];
                            $item->productQtyUnit = $i['productQtyUnit'];
                            $item->productLocation = $i['productLocation'];
                            $item->productQty = $i['productQty'];
                            $item->productPrice = $i['productPrice'];
                            $item->productDiscount = $i['productDiscount'];
                            $item->productRemark = $i['productRemark'];
                            $item->productStandardPrice = $i['productStandardPrice'];
                            $item->productUnitName = trim($i['productUnitName']);
                            $item->approvedSupervisorId = 27;
                        }
                    }
                }
            }

            if ($i['deleted'] == '0' && $i['productQty'] != 0)
                $item->save();
        }


        return $this->invoiceId;

    }

    private function __standardizeDateYmdTOUnix($date)
    {
        $date = explode('-', $date);
        $date = strtotime($date[2] . '-' . $date[1] . '-' . $date[0]);
        return $date;
    }


    private function __prepareInvoices()
    {
        if ($this->action == 'create') {
            Customer::where('customerId', $this->temp_invoice_information['clientId'])->update(['unlock' => 0]);

            if (isset($this->temp_invoice_information['invoiceNumber']))
                $this->invoiceId = $this->temp_invoice_information['invoiceNumber'];
            else
                $this->generateInvoiceId();


            $this->im->invoiceId = $this->invoiceId;
            $this->im->invoiceType = $this->temp_invoice_information['tradingCompany'];
            $this->im->zoneId = $this->temp_invoice_information['zoneId'];
            $this->im->receiveMoneyZone = $this->temp_invoice_information['zoneId'];
            $this->im->customerId = $this->temp_invoice_information['clientId'];
            $this->im->invoiceRemark = $this->temp_invoice_information['invoiceRemark'];
            $this->im->routePlanningPriority = $this->temp_invoice_information['route'];
            $this->im->deliveryTruckId = 0;
            $this->im->invoiceCurrency = 'HKD';
            $this->im->customerRef = $this->temp_invoice_information['referenceNumber'];
            $this->im->invoiceDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']);
            $this->im->deliveryDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']);
            $this->im->dueDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['dueDate']);
            $this->im->paymentTerms = $this->temp_invoice_information['paymentTerms'];
            $this->im->shift = $this->temp_invoice_information['shift'];
            $this->im->created_by = Auth::user()->id;
            $this->im->updated_by = Auth::user()->id;
            $this->im->invoiceStatus = '2';
            $this->im->invoiceDiscount = @$this->temp_invoice_information['discount'];
            $this->im->amount = $this->temp_invoice_information['amount'];
            $this->im->created_at = time();
            $this->im->updated_at = time();
        } elseif ($this->action == 'update') {
            $this->im->invoiceType = $this->temp_invoice_information['tradingCompany'];
            $this->im->zoneId = $this->temp_invoice_information['zoneId'];
            $this->im->receiveMoneyZone = $this->temp_invoice_information['zoneId'];
            $this->im->customerId = $this->temp_invoice_information['clientId'];
            $this->im->routePlanningPriority = $this->temp_invoice_information['route'];
            $this->im->invoiceDiscount = $this->temp_invoice_information['discount'];
            $this->im->invoiceRemark = $this->temp_invoice_information['invoiceRemark'];
            $this->im->shift = $this->temp_invoice_information['shift'];
            $this->im->paymentTerms = $this->temp_invoice_information['paymentTerms'];
            $this->im->customerRef = $this->temp_invoice_information['referenceNumber'];
            $this->im->invoiceDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']);
            $this->im->deliveryDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']);
            $this->im->dueDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['dueDate']);
            $this->im->invoiceStatus = '2';
            $this->im->updated_by = Auth::user()->id;
            $this->im->amount = $this->temp_invoice_information['amount'];

        }
    }


    public function generateInvoiceId()
    {
        $invoiceLength = 6;

        $prefix = date("\Iym-");
        if($this->trade_way == 1)
            $lastInvoice = Invoice::withTrashed()->where('invoiceId', 'like', $prefix . '%')->limit(1)->orderBy('invoiceId', 'Desc')->first();
        else
            $lastInvoice = invoiceTrading::withTrashed()->where('invoiceId', 'like', $prefix . '%')->limit(1)->orderBy('invoiceId', 'Desc')->first();

        if (count($lastInvoice) > 0) {
            // extract latter part
            $i = explode('-', $lastInvoice->invoiceId);
            $nextId = (int)$i[1] + 1;
            $nextInvoiceDate = $prefix . str_pad($nextId, $invoiceLength, '0', STR_PAD_LEFT);
        } else {
            $nextInvoiceDate = $prefix . str_pad('1', $invoiceLength, '0', STR_PAD_LEFT);
        }

        $this->invoiceId = $nextInvoiceDate;

        return $this;
    }


    public function saveInvoice()
    {
        try {
            $this->im->save();
            container::where('id',$this->temp_invoice_information['container_id'])->update(['trade_way'=>$this->trade_way,'invoiceId'=>$this->invoiceId]);
        } catch (Illuminate\Database\QueryException $e) {
            $debugs = new debug();
            $debugs->content = $this->temp_invoice_information['clientId'];
            $debugs->save();
            $this->generateInvoiceId();
            $this->im->invoiceId = $this->invoiceId;
            $this->saveInvoice();
        }
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

        if($i->invoiceStatus != '97' && $i->invoiceStatus != '96')
            $this->backToStock($i->invoiceitem);

        $i->previous_status = $i->invoiceStatus;
        $i->invoiceStatus = 99;
        $i->save();
        $i->delete();

        invoiceitem::where('invoiceId', $invoiceId)->update(['itemStatus'=>99]);
        invoiceitem::where('invoiceId', $invoiceId)->delete();



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
                    if($invoice->lock)
                        return $invoice->invoiceId;
                    else
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


            foreach ($invoices->invoiceItem as $v){
                if ($v->productQtyUnit == 'carton')
                    $v->cost = $v->productDetail->productCost_unit;
                else if($v->productQtyUnit == 'inner')
                    $v->cost = $v->productDetail->productCost_unit/$v->productDetail->productPacking_inner;
                else if ($v->productQtyUnit == 'unit')
                    $v->cost = $v->productDetail->productCost_unit/$v->productDetail->productPacking_inner/$v->productDetail->productPacking_unit;
            }

        }

        return Response::json($invoices);


    }

    public function jsonGetSingleInvoice()
    {



        $invoiceId = Input::get('invoiceId');

        $base = Invoice::where('invoiceId', $invoiceId);

        $invoice = Invoice::categorizePendingInvoice(Invoice::getFullInvoice($base));

        $system = new SystemController();

        $returnInformation = [
            'invoice' => array_values($invoice['categorized'])[0]['invoices'][0],
            'entrieinfo' => array_values($invoice['categorized'])[0]['zoneName'],
            'workingDay' => $system->getPreviousDay(5),
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
            return Response::json(0);
        else if($count>2)
            return Response::json(1);
        else
            return Response::json(0);
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