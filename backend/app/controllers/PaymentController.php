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


       /* if($mode == 'payment'){
            $payment_id = Input::get('payment_id');

            $info = Payment::where('id',$payment_id)->where('used','!=',1)->first();

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

        }*/



     /*   if($mode == 'del'){
            $cheque_id = Input::get('cheque_id');
            $p =Payment::find($cheque_id);
            $p->delete();
        }*/

        if($mode == 'single')
        {
            $payment = Payment::where('id',Input::get('cheque_id'))->with('invoice')->first();


    foreach($payment->invoice as $vv){
      $vv->customerName = $vv->customer_name;

    }



            $vv = $payment;
                $c = explode(',',$vv->customerId);
                $cc = customer::whereIn('customerId',$c)->get();


          $final['payment'] = $payment;
            $final['customer']  = $cc;


            return Response::json($final);

        }else if($mode == 'getChequeList')
        {
            $filter = Input::get('filterData');
            $payments = Payment::select('*');
            //cheque status
            if($filter['status'] != 2)
            {
                $payments->where('used', $filter['status']);
            }

            $payments->where('start_date', '>=',$filter['deliverydate'])->where('end_date','<=',$filter['deliverydate2'])

            ->where('ref_number','Like',$filter['ChequeNumber'].'%')

            ->where('customerId','Like','%'.$filter['clientId'].'%');

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
                   $cc = customerGroup::find($vv->groupId);
                    if(count($cc) < 1)
                        $arr2[$vv->id] = '';
               else
                   $arr2[$vv->id] = $cc->name;

           }



            return Datatables::of($payments)

                ->addColumn('link', function ($model) {
                return '<span onclick="viewCheque(\''.$model->id.'\')" class="btn btn-xs default"><i class="fa fa-search"></i> 檢視</span>';
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