<?php

class PaymentController extends BaseController {

    private function __standardizeDateYmdTOUnix($date)
    {
        $date = explode('-', $date);
        $date = strtotime($date[2].'-'.$date[1].'-'.$date[0]);
        return $date;
    }

    public function addCheque(){

        $i = Input::get('info');

       $amount = Invoice::where('customerId',$i['clientId'])->WhereBetween('deliveryDate',[strtotime($i['startDate']),strtotime($i['endDate'])])->where('paymentTerms',2)->where('invoiceStatus',98)->sum('amount');

        $info = new Payment();

        $c = Customer::where('customerId',$i['clientId'])->first();

        $info->customerId = $i['clientId'];
        $info->deliveryZone = $c['deliveryZone'];
        $info->ref_number = $i['no'];
        $info->bankCode = $i['bankCode'];
        $info->receive_date = $i['receiveDate'];
        $info->start_date = $i['startDate'];
        $info->end_date = $i['endDate'];
        $info->amount = $i['amount'];
        $info->remain = $i['amount'];
        $info->credit = $amount;
        $info->save();
    }

    public function querryCashCustomer(){

        $mode = Input::get('mode');

        if($mode == 'posting'){


            $paid = Input::get('paid');

            foreach ($paid as $k=>$v){
                $i = Invoice::where('invoiceId',$v['id'])->first();
                if($v['paid'] > 0)
                    $i->invoiceStatus = $v['paid'];
                if($v['paid'] == 30)
                    $i->paid_date = $v['date'];
                $i->save();
            }

        }

        if($mode == 'collection')
        {
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
                case 'last day' :
                    $dDateBegin = strtotime("1 day ago 00:00");
                    $dDateEnd = strtotime("1 day ago 23:59");
                    break;
                case 'past-7-days' :
                    $dDateBegin = strtotime("7 days ago 00:00");
                    $dDateEnd = strtotime("today 23:59");
                    break;
                case 'last day' :
                    $dDateBegin = strtotime("1 days ago 00:00");
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
            }else{
                $invoice->wherein('invoiceStatus',['2','20','30']);
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

            $invoices = $invoice->orderby('invoiceId', 'desc')->get();


            return Response::json($invoices);
        }


    }

    public function getClientClearance(){

        $mode = Input::get('mode');

        if($mode == 'payment'){
            $payment_id = Input::get('payment_id');

            $info = Payment::where('id',$payment_id)->where('used','!=',1)->with('Customer')->first();

            $start_date = strtotime($info->start_date);
            $end_date = strtotime($info->end_date);

            $invoice_info = Invoice::whereBetween('deliveryDate',[$start_date,$end_date])->wherein('invoiceStatus',[2,20,98])->where('amount','!=',DB::raw('paid*-1'))->where('customerId',$info->customerId)->get(); 



            foreach ($invoice_info as $v){
                if($v['paid']>0){
                    if($v['invoiceStatus']==98)
                        $v['amount'] = ($v['amount']*-1) - $v['paid'];
                    else
                        $v['amount'] -= $v['paid'];
                }
                $info['sum']+= ($v['invoiceStatus']==98)?$v['amount']*-1:$v['amount'];
            }

            return Response::json($info);

        }

        if ($mode == 'invoice'){

            // pd(Input::all());

            $check_amount = Input::get('amount');

            $start_date = strtotime(Input::get('start_date'));
            $end_date = strtotime(Input::get('end_date'));

            /*  Paginator::setCurrentPage((Input::get('start')+20) / Input::get('length'));

              $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;

              $invoice = Invoice::with('invoiceItem')->whereBetween('deliveryDate',[$start_date,$end_date])->where('customerId',Input::get('customerId'))->paginate($page_length);
  */

            $invoice = Invoice::whereBetween('deliveryDate',[$start_date,$end_date])->where('customerId',Input::get('customerId'))->wherein('invoiceStatus',[2,20,98])->where('amount','!=',DB::raw('paid*-1'))->OrderBy('deliveryDate')->get();


            foreach($invoice as $c)
            {
                $c->realAmount = $c->realAmount - $c->paid;

                if($check_amount >= $c->realAmount){
                    $c->settle= $c->realAmount;
                }else
                    $c->settle= $check_amount;

                $check_amount -= $c->realAmount;
                if($check_amount < 0) $check_amount = 0;

            }



            return Response::json($invoice);
        }

        if($mode == 'posting'){
            $paid = Input::get('paid');


            $set_amount =0;
            foreach ($paid as $k=>$v){
                $i = Invoice::where('invoiceId',$v['id'])->first();
                $i->paid += $v['settle'];
                $set_amount += $v['settle'];


                if($i->amount == $i->paid || $v['discount'] == 1)
                    $i->invoiceStatus = 30;
                $i->discount = $v['discount'];
                $i->save();
            }

            $cheque_id = Input::get('cheque_id');
            $p = Payment::find($cheque_id);
            $p->remain = $p->remain-$set_amount;
            if($p->remain == 0)
                $p->used = 1;
            $p->save();


        }

        if($mode == 'del'){
            $cheque_id = Input::get('cheque_id');
            $p =Payment::find($cheque_id);
            $p->delete();
        }



        if($mode == 'getChequeList')
        {
            $filter = Input::get('filterData');
            Paginator::setCurrentPage((Input::get('start')+10) / Input::get('length'));
            $customer = Payment::select('*');


            //cheque status
            if($filter['status'] != 2)
            {
                $customer->where('used', $filter['status']);
            }


            // client id
            if($filter['clientId'])
            {
                $customer->where('customerId', $filter['clientId']);
            }

            $permittedZone = explode(',', Auth::user()->temp_zone);

            if($filter['zone'] != '')
            {
                $customer->where('deliveryZone', $filter['zone']['zoneId']);
            }else{
                $customer->whereIn('deliveryZone',$permittedZone);
            }


            // query

            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
            $customer = $customer->with('Customer')->paginate($page_length);


            foreach($customer as $c)
            {
                if($c->used == true)
                    $c->link = '已處理';
                else
                    $c->link = '<span onclick="processCheque(\''.$c->id.'\')" class="btn btn-xs default"><i class="glyphicon glyphicon-cog"></i> 處理</span>';
                $c->delete = '<span onclick="delCheque(\''.$c->id.'\')" class="btn btn-xs default"><i class="fa glyphicon glyphicon-remove"></i> 刪除</span>';
            }

            return Response::json($customer);
        }


    }



}