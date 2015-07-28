<?php

class PaymentController extends BaseController {

    public function addCheque(){








            $paid = Input::get('paid');


        $ij = Input::get('filterData');
            $set_amount =0;
            foreach ($paid as $k=>$v){
                $i = Invoice::where('invoiceId',$v['id'])->first();
                $i->paid += $v['settle'];
                $set_amount += $v['settle'];


                if($i->amount == $i->paid || $v['discount'] == 1)
                    $i->invoiceStatus = 30;
                $i->discount = $v['discount'];
                if($ij['discount'])
                    $i->discount = 1;
                $i->save();
            }




        $info = new Payment();

        //  $c = Customer::where('customerId',$i['clientId'])->first();

        $info->customerId = $ij['customerId'];
        $info->ref_number = $ij['no'];
        $info->bankCode = $ij['bankCode'];
        $info->receive_date = $ij['receiveDate'];
        $info->start_date = $ij['startDate'];
        $info->end_date = $ij['endDate'];
        $info->amount = $ij['amount'];
        // $info->credit = $amount;


        if($info->remain == 0)
            $info->used = 1;
        if($ij['discount']){
            $info->used = 1;
            $info->remain = 0;
            $info->discount = $set_amount-$ij['amount'];
        }else{
            $info->remain = $ij['amount']-$set_amount;
        }

        $info->save();

        foreach ($paid as $k=>$v){
            $iq = Invoice::where('invoiceId',$v['id'])->first();
            $iq->cheque_id = $info->id;
            $iq->save();
        }
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

            if($filter['clientId'] =='' && $filter['invoiceNumber'] == ''){
                return Response::json();
            }

            //dd($dDateBegin, $dDateEnd, date("Y-m-d H:i:s", $dDateBegin), date("Y-m-d H:i:s", $dDateEnd));

            $invoice = Invoice::select('customerName_chi','invoiceId','amount','invoice.zoneId','deliveryDate','invoiceStatus')->leftjoin('customer', 'customer.customerId', '=', 'invoice.customerId')->where('deliveryDate','>',strtotime("-4 days"));

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
            if($filter['clientId'] != '')
            {
                $invoice->where('invoice.customerId', $filter['clientId']);
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
        $filter = Input::get('filterData');

        $start_date = strtotime($filter['startDate']);
        $end_date = strtotime($filter['endDate']);

        if($mode  == 'processCustomer'){

            $info['sum'] = 0;
            $customerId = explode(",", $filter['customerId']);



            $invoice_info = Invoice::whereBetween('deliveryDate',[$start_date,$end_date])->wherein('invoiceStatus',[2,20,98])->where('amount','!=',DB::raw('paid*-1'))->where('discount',0)->whereIn('customerId',$customerId)->get();

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
            $customerId = explode(",", $filter['customerId']);

         //   $check_amount = Input::get('amount');


            $invoice = Invoice::whereBetween('deliveryDate',[$start_date,$end_date])->whereIn('customerId',$customerId)->wherein('invoiceStatus',[2,20,98])->where('amount','!=',DB::raw('paid*-1'))->where('discount',0)->OrderBy('customerId','deliveryDate')->get();


        foreach($invoice as &$c)
            {
                          $c->realAmount = $c->realAmount - $c->paid;

              /*  if($check_amount >= $c->realAmount){
                    $c->settle= $c->realAmount;
                }else
                    $c->settle= $check_amount;

                $check_amount -= $c->realAmount;
                if($check_amount < 0) $check_amount = 0;*/

            }



            return Response::json($invoice);
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