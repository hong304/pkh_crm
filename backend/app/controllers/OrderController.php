<?php

class OrderController extends BaseController
{

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

      //  pd($product);

        foreach ($product as $p) {
            if ($p['dbid'] != '' && $p['deleted'] == 0)
                $itemIds[] = $p['dbid'];
            if ($p['dbid'] == '')
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
        $return = array('pendingapproval' => '', 'myrejectedinvoices' => '', 'pendingpring' => 0);

        $invoices = Invoice::select(DB::raw('zoneId, invoiceStatus, count(invoiceId) AS counts'))
            ->wherein('invoiceStatus', ['1', '3'])
            ->wherein('zoneId', explode(',', Auth::user()->temp_zone))
            ->groupBy('invoiceStatus', 'zoneId')
            ->with('zone')
            ->get();

        $summary['countInDataMart'] = 0;

        foreach ($invoices as $invoice) {
            $summary[$invoice->invoiceStatus]['breakdown'][$invoice->zoneId] = [
                'zoneId' => $invoice->zoneId,
                'counts' => $invoice->counts,
                'zoneText' => $invoice->zone->zoneName,
            ];
            $summary[$invoice->invoiceStatus]['countInDataMart'] = (isset($summary[$invoice->invoiceStatus]['countInDataMart']) ? $summary[$invoice->invoiceStatus]['countInDataMart'] : 0) + $invoice->counts;

            $summary['countInDataMart'] += $invoice->counts;
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

        $summary['open'] = Invoice::where('invoiceStatus', 2)->wherein('zoneId', explode(',', Auth::user()->temp_zone))->count();

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
        $itemIds = array('斤', '樽', '桶', '排', '箱');

        $ids = "'" . implode("','", $itemIds) . "'";

        $mode = Input::get('mode');

        if ($mode == 'collection') {
            Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);

            $filter = Input::get('filterData');

            // start with date filter
            switch ($filter['deliverydate']) {
                case 'today' :
                    $dDateBegin = strtotime("today 00:00");
                    $dDateEnd = strtotime("today 23:59");
                    break;
                case 'coming-7-days' :
                    $dDateBegin = strtotime("today 00:00");
                    $dDateEnd = strtotime("+1 week");
                    break;
                case '1daylr' :
                    $dDateBegin = strtotime("2 days ago 00:00");
                    $dDateEnd = strtotime("+2 day");
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
            if ($filter['status'] != '0') {
                $invoice->where('invoiceStatus', $filter['status']);
            }
            if ($filter['status'] == '99') {
                $invoice->withTrashed();
            }

            // client id
            if ($filter['clientId'] != '0') {
                $invoice->where('customerId', $filter['clientId']);
            }

            // invoice number
            if ($filter['invoiceNumber'] != '') {
                $invoice->where('invoiceId', 'LIKE', '%' . $filter['invoiceNumber'] . '%');
            }

            // created by
            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;


            /*  $invoices = $invoices->with(['invoiceItem'=>function($query) use($ids){
                  $query->orderByRaw(DB::raw("FIELD(productUnitName, $ids) DESC"));
              }])->with( 'client', 'staff')->get();*/


            $invoices = $invoice->with('client', 'staff')->orderby('invoiceId', 'desc')->paginate($page_length);

            foreach ($invoices as $invoice) {
                $invoice->link = '<span onclick="viewInvoice(\'' . $invoice->invoiceId . '\')" class="btn btn-xs default"><i class="fa fa-search"></i> 檢視</span>';
            }
        } elseif ($mode == 'single') {
            $invoices = Invoice::where('invoiceId', Input::get('invoiceId'))
                ->with(['invoiceItem' => function ($query) use ($ids) {
                    $query->orderByRaw(DB::raw("FIELD(productUnitName, $ids) DESC"));
                }])->with('products', 'client', 'staff', 'printqueue', 'audit', 'audit.User')
                ->withTrashed()
                ->first();

        }
        //dd(DB::getQueryLog());

        // pd($invoices);
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
        $sql = "SELECT * FROM Invoice i LEFT JOIN InvoiceItem ii ON i.invoiceId=ii.invoiceId WHERE customerId = '" . $customerId . "' AND ii.productId = '" . $productId . "' order by ii.invoiceItemId desc";
        $items = DB::select(DB::raw($sql));
        if ($items == null)
            return Response::json($items);
        return Response::json($items[0]);
    }

    public function jsonGetSameDayOrder()
    {
        $customerId = Input::get('customerId');
        $dueDate = Input::get('dueDate');

        $dueDate = strtotime($dueDate);

        $invoice_id = Invoice::where('customerId', $customerId)->where('dueDate', $dueDate)->first();

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