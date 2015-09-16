<?php

class PaymentController extends BaseController {


    public function addCheque(){
        $paid = Input::get('paid');


        $ij = Input::get('filterData');





        $rules = [
            'no' => 'required',
            'amount' => 'required',
            'customerId' => 'required_without:customer_group_id',
            'customer_group_id' => 'required_without:customerId',
        ];


        $validator = Validator::make($ij, $rules);
        $errorMessage = '';
        if ($validator->fails())
        {
            $info = $validator->messages()->all();
            foreach($info as $a)
            {
                $errorMessage .= "$a\n";
            }
            return $errorMessage;

        }



        $set_amount =0;
        foreach ($paid as $k=>$v){
            $i = Invoice::where('invoiceId',$v['id'])->first();
            $i->paid += $v['settle'];
            $set_amount += $v['settle'];



            if($i->amount == $i->paid || $v['discount'] == 1)
                $i->invoiceStatus = 30;
            $i->discount = $v['discount'];
            if(isset($ij['discount']))
                $i->discount = 1;
            $i->save();
        }




        $info = new Payment();

        //  $c = Customer::where('customerId',$i['clientId'])->first();

        $info->customerId = $ij['customerId'];
        $info->groupId = $ij['customer_group_id'];
        $info->ref_number = $ij['no'];
        $info->bankCode = $ij['bankCode'];
        $info->receive_date = $ij['receiveDate'];
        $info->start_date = $ij['startDate'];
        $info->end_date = $ij['endDate'];
        $info->amount = $ij['amount'];
        // $info->credit = $amount;


        if($info->remain == 0)
            $info->used = 1;
        if(isset($ij['discount'])){
            $info->used = 1;
            $info->remain = 0;
            $info->discount = $set_amount-$ij['amount'];
        }else{
            $info->remain = $ij['amount']-$set_amount;
        }

        $info->save();

        foreach ($paid as $k=>$v){
            $iq = Invoice::where('invoiceId',$v['id'])->first();
            $iq->payment()->attach($info->id,['amount'=>$iq->amount,'paid'=>$v['settle']]);
            $iq->save();
        }
    }

    public function querryCashCustomer(){

        $mode = Input::get('mode');

        if($mode == 'posting'){

            $paidinfo = Input::get('paidinfo');
            $discount_taken = Input::get('discount');

            if($paidinfo['no']!=''){
                $payment = new Payment();
                $payment->paymentType = 'COD';
                $payment->bankCode = $paidinfo['bankCode'];
                $payment->ref_number = $paidinfo['no'];
                $payment->start_date = $paidinfo['receiveDate'];
                $payment->end_date =  $paidinfo['receiveDate'];
                $payment->receive_date = $paidinfo['receiveDate'];
                $payment->amount = $paidinfo['amount'];


                if($paidinfo['amount'] <= $paidinfo['paid'])
                    $payment->used = 1;

                if($paidinfo['remain']>0)
                    $payment->remain = $paidinfo['remain'] - $paidinfo['paid'];
                else
                    $payment->remain = $paidinfo['amount'] - $paidinfo['paid'];

                $payment->customerId = $paidinfo['clientId'];

                $payment->save();

                $i = Invoice::where('invoiceId',$paidinfo['invoiceId'])->first();
                $i->paid += $paidinfo['paid'];
                $i->invoiceStatus = Input::get('paymentStatus');
                if($discount_taken>0){
                    $i->discount = 1;
                    $i->discount_taken += $discount_taken;
                }
                $i->save();

                if($discount_taken>0){
                    $i->payment()->attach($payment->id,['amount'=>$i->amount,'paid'=>$paidinfo['paid'],'discount_taken'=>$discount_taken]);
                }else
                    $i->payment()->attach($payment->id,['amount'=>$i->amount,'paid'=>$paidinfo['paid']]);
            }else if($paidinfo['cashAmount']!=0){
                $payment1 = new Payment();
                $payment1->paymentType = 'COD';
                $payment1->bankCode = 'cash';
                $payment1->ref_number = 'cash';
                $payment1->start_date = $paidinfo['receiveDate'];
                $payment1->end_date =  $paidinfo['receiveDate'];
                $payment1->receive_date = $paidinfo['receiveDate'];
                $payment1->amount = $paidinfo['cashAmount'];
                $payment1->customerId = $paidinfo['clientId'];
                $payment1->save();

                $i = Invoice::where('invoiceId',$paidinfo['invoiceId'])->first();
                $i->paid += $paidinfo['cashAmount'];
                $i->invoiceStatus = Input::get('paymentStatus');
                if($discount_taken>0) {
                    $i->discount = 1;
                    $i->discount_taken += $discount_taken;
                }
                $i->save();

                if($discount_taken>0) {
                   $i->payment()->attach($payment1->id, ['amount' => $i->amount, 'paid' => $paidinfo['cashAmount'],'discount_taken'=>$discount_taken]);
                }else{
                    $i->payment()->attach($payment1->id, ['amount' => $i->amount, 'paid' => $paidinfo['cashAmount']]);
                }
            }else if($paidinfo['amount'] ==0 && $paidinfo['cashAmount']==0){
                $i = Invoice::where('invoiceId',$paidinfo['invoiceId'])->first();
                $i->invoiceStatus = Input::get('paymentStatus');
                if($i->amount == $i->paid)
                    $i->paid=0;
                  $i->save();
            }

        }

        if($mode == 'collection')
        {
            $filter = Input::get('filterData');



            $invoice = Invoice::select('invoiceId','discount_taken','amount','paid','invoice.zoneId','deliveryDate','invoiceStatus','invoice.customerId','paymentTerms');

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
            if($filter['invoiceNumber'] == '' && $filter['customerId']=='')
            {
                $invoice->whereBetween('invoice.deliverydate', [strtotime($filter['deliverydate']),strtotime($filter['deliverydate2'])]);
                $invoice->where('invoiceStatus', $filter['status']);

            }else if ($filter['invoiceNumber'] != ''){
                $invoice->where('invoiceId', 'LIKE', '%'.$filter['invoiceNumber'].'%');
            }else if($filter['customerId']!=''){
                $invoice->WhereHas('client', function($q) use($filter)
                {
                    $q->where('customerId', $filter['customerId']);
                });
                //$invoice->whereIn('invoiceStatus', [2,20]);
                $invoice->where('invoiceStatus', $filter['status']);
            }
                $invoice->where('paymentTerms','=',1);
            // created by

            $invoices = $invoice->orderby('deliveryDate', 'asc');

            return Datatables::of($invoices)
                ->addColumn('link', function ($payment) {
                    if(Auth::user()->can('edit_cashCustomer') && ($payment->invoiceStatus == '20'||$payment->invoiceStatus == '30'||$payment->invoiceStatus == '2')){
                        return '<span onclick="editInvoicePayment(\''.$payment->invoiceId.'\',\''.$payment->customerId.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 更改</span>';
                    }else
                        return '';
                })->addColumn('details', function ($payment) {
                    return '<span onclick="viewInvoicePayment(\''.$payment->invoiceId.'\',\''.$payment->customerId.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 詳情</span>';
                })->addColumn('customerName', function ($payment) {
                    return $payment->customer_name;
                })

                ->make(true);
        }

        if($mode == 'checkChequeExist'){
            $paidinfo = Input::get('paidinfo');
            $p = Payment::where('bankCode',$paidinfo['bankCode'])->where('ref_number',$paidinfo['no'])->orderBy('id','desc')->first();
            return Response::json($p);
        }

        if($mode == 'single'){

            $p=DB::table('invoice_payment')->select('invoice_payment.amount as totalamount','bankCode','ref_number','receive_date','paid','id')->leftJoin('payments', function($join) {
                $join->on('payment_id', '=', 'payments.id');
            })->where('invoice_id','=',Input::get('invoiceId'))->get();

            return Response::json($p);
        }

        if($mode == 'paymentHistory'){
            $ph = Invoice::select('deliveryDate','invoiceId','amount','paid',DB::Raw('amount-paid as owe'))
                 ->where(function ($query) {
                    $query->where('customerId',Input::get('customerId'))->where('invoiceStatus','20')->where('paymentTerms','1')->where('discount',false);
                })->orWhere('invoiceId',Input::get('invoiceId'))
                ->get();

            return Response::json($ph);
        }
    }

    public function getClearance(){
        $mode = Input::get('mode');
        $filter = Input::get('filterData');


        $start_date = strtotime($filter['startDate']);
        $end_date = strtotime($filter['endDate']);

        $customer = [];
        $customer2 = [];
        if($filter['customer_group_id'] != '')
            $customer = customer::leftJoin('customer_groups', function($join) {
                $join->on('customer_groups.id', '=', 'customer.customer_group_id');
            })->where('customer_group_id',$filter['customer_group_id'])->lists('customerId');

        if($filter['customerId'] != ''){
            $customer2 = explode(",", $filter['customerId']);
        }

        if(count($customer2)>0){
            for($i = 0; $i < count($customer2); $i++){
                $rules['customerId.' . $i] = 'exists:customer,customerId';
                $messages = ['exists' => 'Customer ID:'.$customer2[$i].' does not exists.'];
            }

            $arr = ['customerId'=>$customer2];

            $validator = Validator::make($arr, $rules,$messages);
            $errorMessage['error'] = '';
            if ($validator->fails())
            {
                $info = $validator->messages()->all();
                foreach($info as $a)
                {
                    $errorMessage['error'] .= "$a\n";
                }
                return $errorMessage;

            }
        }

        $customerId = array_merge($customer, $customer2);



        if($mode  == 'processCustomer'){

            $sum = 0;

            //  $invoice_info = Invoice::whereBetween('deliveryDate',[$start_date,$end_date])->wherein('invoiceStatus',[2,20,98])->where('amount','!=',DB::raw('paid*-1'))->where('discount',0)->whereIn('customerId',$customerId)->with('client')->get();
            $invoice_info = Invoice::whereBetween('deliveryDate',[$start_date,$end_date])->whereIn('customerId',$customerId)->wherein('invoiceStatus',[2,20,98])->where('amount','!=',DB::raw('paid*-1'))->where('discount',0)->OrderBy('customerId','asc')->orderBy('deliveryDate')->get();

            $discount = Customer::select('discount')->whereIn('customerId',$customerId)->first();
            foreach ($invoice_info as $v){
                if($v['paid']>0){
                    if($v['invoiceStatus']==98)
                        $v['amount'] = ($v['amount']*-1) - $v['paid'];
                    else
                        $v['amount'] -= $v['paid'];
                }
                $v['realAmount'] = $v['realAmount'] - $v['paid'];
                $v['customer_name'] = $v['customer_name'];
                $sum += ($v['invoiceStatus']==98)?$v['amount']*-1:$v['amount'];
            }
            $invoice['data'] = $invoice_info;
            $invoice['sum'] = $sum;
            $invoice['discount'] = $discount->discount;

            return Response::json($invoice);


        }

    }


    public function getClientClearance(){
        $mode = Input::get('mode');


        if($mode == 'single')
        {
            $payment = Payment::where('id',Input::get('cheque_id'))->with('invoice')->first();
            foreach($payment->invoice as $vv){
                $vv->customerName = $vv->customer_name;

            }

            $vv = $payment;

            $c = explode(',',$vv->customerId);
            if($vv->groupId != 0)
                $cc = customer::whereIn('customerId',$c)->orwhere('customer_group_id',$vv->groupId)->get();
            else
                $cc = customer::whereIn('customerId',$c)->get();

            $final['payment'] = $payment;
            $final['customer']  = $cc;


            return Response::json($final);

        }

        //支票列表
        if($mode == 'getChequeList')
        {

            if(Input::get('action')=='cod')
                if(!Auth::user()->can('view_cashCustomerCheque'))
                    pd('Access denied');
            else if(Input::get('action')=='credit')
                if(!Auth::user()->can('view_cheque'))
                    pd('Access denied');
            $filter = Input::get('filterData');
            $payments = Payment::select('payments.id as id','ref_number','start_date','end_date','customerId','groupId','amount','remain')->where('paymentType',Input::get('action'));
            //cheque status
            if($filter['status'] != 2)
            {
                $payments->where('used', $filter['status']);
            }



            if($filter['ChequeNumber'] == '' && $filter['clientId'] == '' && $filter['groupName'] == '')
                $payments->where('start_date', '>=',$filter['deliverydate'])->where('end_date','<=',$filter['deliverydate2']);
            else{
                if($filter['groupName'] != ''){
                    $payments->leftJoin('customer_groups', function($join) {
                        $join->on('customer_groups.id', '=', 'payments.groupId');
                    })->where('customer_groups.name','LIKE','%'.$filter['groupName'].'%');
                }else{
                    $payments->where('ref_number','Like',$filter['ChequeNumber'].'%')
                        ->where('customerId','Like','%'.$filter['clientId'].'%');
                }
            }
            /*
          // client id
          if($filter['clientId']!='')
          {
              $customer->where('customerId', $filter['clientId']);
          }


             $permittedZone = explode(',', Auth::user()->temp_zone);

           if($filter['zone'] != '')
            {
                $customer->where('deliveryZone', $filter['zone']['zoneId']);
            }else{
                $customer->whereIn('deliveryZone',$permittedZone);
            }*/

            $payments = $payments->OrderBy('start_date','desc');


            $p = $payments->get();

            $arr = [];
            $arr1 = [];
            $arr2 = [];
            foreach($p as $vv){

                if(!isset($arr[$vv->id]))
                    $arr[$vv->id] = '';
                if(!isset($arr1[$vv->id]))
                    $arr1[$vv->id] = '';

                $c = explode(',',$vv->customerId);
                $cc = customer::whereIn('customerId',$c)->get();

                if(count($cc) < 1){
                    $arr[$vv->id] ='';
                    $arr1[$vv->id] = '';
                }

                foreach ($cc as $g){
                    $arr[$vv->id] .= $g->customerName_chi.'<br/>';
                    $arr1[$vv->id] .= $g->customerId.'<br/>';
                }

                if(!isset($arr2[$vv->id]))
                    $arr2[$vv->id] = '';
                $cc1 = customerGroup::find($vv->groupId);
                if(count($cc1) < 1)
                    $arr2[$vv->id] = '';
                else
                    $arr2[$vv->id] = $cc1->name;

            }



            return Datatables::of($payments)

                ->addColumn('link', function ($payment) {
                    return '<span onclick="viewCheque(\''.$payment->id.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 檢視</span>';
                })
                ->addColumn('customName', function ($user) use($arr) {
                    return $arr[$user->id];
                })->addColumn('customID', function ($user) use($arr1) {
                    return $arr1[$user->id];
                })->addColumn('customGroup', function ($user) use($arr2) {
                    return $arr2[$user->id];
                })->make(true);


            /*  foreach($customer as $c)
              {
                  if($c->used == true){
                     // $c->link = '已處理';
                     // $c->delete = '不可刪除';
                  }else{
                     // $c->link = '<span onclick="processCheque(\''.$c->id.'\')" class="btn btn-xs default"><i class="glyphicon glyphicon-cog"></i> 處理</span>';
                    //  $c->delete = '<span onclick="delCheque(\''.$c->id.'\')" class="btn btn-xs default"><i class="fa glyphicon glyphicon-remove"></i> 刪除</span>';
                  }
              }*/


        }


    }



}