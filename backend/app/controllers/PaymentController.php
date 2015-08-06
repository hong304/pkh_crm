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
            $paid = Input::get('paid');
            $paidinfo = Input::get('paidinfo');

if($paidinfo['no']!=''){
            $payment = new Payment();
    $payment->bankCode = $paidinfo['bankCode'];
            $payment->ref_number = $paidinfo['no'];
                $payment->start_date = $paidinfo['receiveDate'];
                    $payment->end_date =  $paidinfo['receiveDate'];
                        $payment->receive_date = $paidinfo['receiveDate'];
                            $payment->amount = $paidinfo['amount'];
                                $payment->used = 1;
                                    $payment->remain = 0;
            $payment->customerId = $paid[0]['customerId'];
            $payment->save();
}

            if($paidinfo['cashAmount']!=0){
                $payment1 = new Payment();
                $payment1->bankCode = 'cash';
                $payment1->ref_number = 'cash';
                $payment1->start_date = $paidinfo['receiveDate'];
                $payment1->end_date =  $paidinfo['receiveDate'];
                $payment1->receive_date = $paidinfo['receiveDate'];
                $payment1->amount = $paidinfo['cashAmount'];
                $payment1->used = 1;
                $payment1->remain = 0;
                $payment1->customerId = $paid[0]['customerId'];
                $payment1->save();
            }


            foreach ($paid as $k=>$v){
                $i = Invoice::where('invoiceId',$v['id'])->first();
                $i->paid += $paidinfo['cashAmount'];
                $i->paid += $paidinfo['amount'];

                if($v['paid'] > 0)
                    $i->invoiceStatus = $v['paid'];
                if($v['paid'] == 30)
                    $i->paid_date = $v['date'];
                $i->save();

                if($paidinfo['no']!='')
                $i->payment()->attach($payment->id,['amount'=>$i->amount,'paid'=>$paidinfo['amount']]);
                if($paidinfo['cashAmount']!=0)
                $i->payment()->attach($payment1->id,['amount'=>$i->amount,'paid'=>$paidinfo['cashAmount']]);
            }

        }

        if($mode == 'collection')
        {
            $filter = Input::get('filterData');

            $invoice = Invoice::select('customerName_chi','invoiceId','amount','paid','invoice.zoneId','deliveryDate','invoiceStatus','invoice.customerId')->leftjoin('customer', 'customer.customerId', '=', 'invoice.customerId')->where('deliveryDate','>',strtotime("-7 days"));

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
            if($filter['invoiceNumber'] == '')
            {
                $invoice->where('invoice.deliverydate', strtotime($filter['deliverydate']));
                $invoice->where('invoiceStatus', $filter['status']);
            }else{
                $invoice->where('invoiceId', 'LIKE', '%'.$filter['invoiceNumber'].'%');

            }


            // created by

            $invoices = $invoice->orderby('invoiceId', 'desc');



            return Datatables::of($invoices)

                ->addColumn('link', function ($payment) {
                    return '<span onclick="editInvoicePayment(\''.$payment->id.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 更改</span>';
                })->make(true);
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

        if($mode == 'getChequeList')
        {


            $filter = Input::get('filterData');
            $payments = Payment::select('payments.id as id','ref_number','start_date','end_date','customerId','groupId','amount','remain');
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