<?php

class financeCashController extends BaseController {

    private $_date = '';
    private $_date1 = '';
    private $_status = '';

    public function delPayment(){
        $payment_id = Input::get('id');
        $payment = Payment::where('id',$payment_id)->with('invoice')->get()->first();
        $i = Invoice::where('invoiceId',$payment->invoice[0]->invoiceId)->first();
        $i->paid = $i->paid -$payment->invoice[0]->pivot->paid;
        $i->invoiceStatus = '20';
        if($payment->invoice[0]->pivot->discount_taken>0)
            $i->discount = 0;
        $i->save();
        $payment->invoice()->detach($payment->invoice[0]->invoiceId);
        $payment->delete();
        return Response::json(1);
    }

    public function getPaymentDetails(){

        $filter = Input::get('filterData');

        $this->_date = (isset($filter['deliverydate']) ? strtotime($filter['deliverydate']) : strtotime("today"));
        $this->_date1 = (isset($filter['deliverydate2']) ? strtotime($filter['deliverydate2']) : strtotime("today"));
        $this->_status = $filter['status'];

        $invoice = Invoice::select('invoiceId','amount','paid','invoice.zoneId','deliveryDate','invoiceStatus','invoice.customerId','paymentTerms')
            ->whereBetween('invoice.deliverydate', [$this->_date, $this->_date1])
            ->where('invoiceStatus',$this->_status)
            ->where('paymentTerms','=',1)->with('payment','client')->get()->toArray();
     //  pd($invoice);




        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';

        $i=3;
        $objPHPExcel = new PHPExcel ();

        $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Delivery Date');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', date('Y-m-d',$this->_date));
        $objPHPExcel->getActiveSheet()->setCellValue('C1', 'To');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', date('Y-m-d',$this->_date1));


        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '訂單編號');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, '送貨日期');
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, '車線');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, '客戶');
        $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, '金額');
        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i, '尚欠');
        $objPHPExcel->getActiveSheet()->setCellValue('G'.$i, '現狀態');
        $objPHPExcel->getActiveSheet()->setCellValue('H'.$i, '');
        $objPHPExcel->getActiveSheet()->setCellValue('I'.$i, '支票號碼/現金');
        $objPHPExcel->getActiveSheet()->setCellValue('J'.$i, '總數');
        $objPHPExcel->getActiveSheet()->setCellValue('K'.$i, '已付');
        $objPHPExcel->getActiveSheet()->setCellValue('L'.$i, '支付日期');

//   $invoice = Invoice::select('invoiceId','amount','paid','invoice.zoneId','deliveryDate','invoiceStatus','invoice.customerId','paymentTerms')

        $i += 1;
        foreach ($invoice as $k => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $v['invoiceId']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, date('Y-m-d',$v['deliveryDate']));
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $v['zoneId']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $v['client']['customerName_chi']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $v['amount']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $v['remain']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $v['invoiceStatusText']);

            if(isset($v['payment']))
                foreach($v['payment'] as $k => $vv){
                    $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $vv['ref_number']);
                    $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, $vv['amount']);
                    $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $vv['pivot']['paid']);
                    $objPHPExcel->getActiveSheet()->setCellValue('L' . $i, $vv['start_date']);
                    $i++;
                }
            $i++;
        }

        foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
            // $calculatedWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($col)->getWidth();
            //$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(5);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.date('Ymd',$this->_date).'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }

    public function getClearance(){
        $mode = Input::get('mode');
        $filter = Input::get('filterData');


        $start_date = strtotime($filter['startDate']);
        $end_date = strtotime($filter['endDate']);

        $customer = [];
        $customer2 = [];

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

    public function addCheque(){
        $paid = Input::get('paid');


        $ij = Input::get('filterData');

        $rules = [
            'no' => 'required',
            'amount' => 'required',
            'customerId' => 'required',
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


        $info->customerId = $ij['customerId'];
        $info->ref_number = $ij['no'];
        $info->bankCode = $ij['bankCode'];
        $info->receive_date = $ij['receiveDate'];
        $info->start_date = $ij['startDate'];
        $info->end_date = $ij['endDate'];
        $info->amount = $ij['amount'];


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
}