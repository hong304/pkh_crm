<?php

class financeCashController extends BaseController {

    private $_date = '';
    private $_date1 = '';
    private $_status = '';

    public function delPayment(){
        $payment_id = Input::get('id');
        $payment = Payment::where('id',$payment_id)->with('invoice')->get()->first();



        foreach($payment->invoice as $k => $v){
            $i = Invoice::where('invoiceId',$v->invoiceId)->first();
            $i->paid = $i->paid -$v->pivot->paid;

            if($i->invoiceStatus == 30)
                $i->invoiceStatus = '20';
            $i->manual_complete = 0;
            if($i->discount_taken!=0){
                $i->discount = 0;
                $i->discount_taken = 0;
            }
           /* if($v->pivot->discount_taken!=0){
                $i->discount -= $v->pivot->discount_taken;
                $i->discount = 0;
            }*/
            $i->save();
            $payment->invoice()->detach($v->invoiceId);
        }
        $payment->delete();
        return Response::json(1);
    }

    public function getPaymentDetails(){

        $filter = Input::get('filterData');

        $this->_date = (isset($filter['deliverydate']) ? strtotime($filter['deliverydate']) : strtotime("today"));
        $this->_date1 = (isset($filter['deliverydate2']) ? strtotime($filter['deliverydate2']) : strtotime("today"));
        $this->_status = $filter['status'];

	if(isset($filter['zone']['zoneId'])) {
            $this->_zone = explode(',', $filter['zone']['zoneId']);
        }
        else
        {
            $this->_zone = '';
        }

        $invoice = Invoice::select('invoiceId','amount','paid','invoice.zoneId','deliveryDate','invoiceStatus','invoice.customerId','paymentTerms')
            ->whereBetween('invoice.deliverydate', [$this->_date, $this->_date1]);
	
	if($this->_zone != '') {
		$invoice->where('invoice.zoneId', $this->_zone);
	}
	$invoice->where('invoice.paymentTerms',1);

        if($this->_status == 30){
            $invoice->where('invoiceStatus',$this->_status)->where('manual_complete',1);
        }            else
            $invoice->where('invoiceStatus',$this->_status);
        $invoice=$invoice->where('paymentTerms','=',1)->with('payment','client')->get()->toArray();
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

       $invoice_info = Invoice::whereBetween('deliveryDate',[$start_date,$end_date])->whereIn('customerId',$customerId)->wherein('invoiceStatus',[2,20,98])->where('amount','!=',DB::raw('paid'))->where('manual_complete',false)->where('discount',0)->OrderBy('customerId','asc')->orderBy('deliveryDate')->get();
           // $discount = Customer::select('discount')->whereIn('customerId',$customerId)->first();



            foreach ($invoice_info as $v){
                if($v['paid']!=0)
                        $v['amount'] -= $v['paid'];

                $v['realAmount'] = $v['realAmount'] - $v['paid'];
                $v['customer_name'] = $v['customer_name'];
                $sum += $v['amount'];
            }
            $invoice['data'] = $invoice_info;
            $invoice['sum'] = $sum;
         //   $invoice['discount'] = $discount->discount;

            return Response::json($invoice);


        }



    }

    public function addCheque(){
        $paid = Input::get('paid');
        $ij = Input::get('filterData');

        $rules = [
            'customerId' => 'required',
            'paymentType' => 'required',
            'no' => 'required_if:paymentType,cheque',
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
            if($v['settle']!=0){
                $i = Invoice::where('invoiceId',$v['id'])->first();

                $i->paid += $v['settle'];
                $set_amount += $v['settle'];



                if($i->paid >= $i->amount || $v['discount'] == 1)
                    if($i->invoiceStatus == 2 || $i->invoiceStatus == 20){
                        $i->invoiceStatus = 30;
                        $i->manual_complete = 1;
                    }
                $i->discount = $v['discount'];
                if(isset($ij['discount']))
                    $i->discount = 1;
                $i->save();
            }
        }




        $info = new Payment();
        $info->customerId = $ij['customerId'];

        if($ij['paymentType']=='cash'){
            $info->ref_number = 'cash';
            $ij['amount'] = $ij['cashAmount'];
        }else{
            $info->ref_number = $ij['no'];
        }
        $info->amount = $ij['amount'];
        $info->receive_date = $ij['receiveDate'];
        $info->start_date = $ij['startDate'];
        $info->end_date = $ij['endDate'];
        $info->remark = $ij['remark'];
        $info->paymentType = 'COD';


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

            $iq = Invoice::where('invoiceId', $v['id'])->first();
            if($v['settle']!=0) {
                $iq->payment()->attach($info->id, ['amount' => $iq->amount, 'paid' => $v['settle']]);
                $iq->save();
            }
        }
    }
}
