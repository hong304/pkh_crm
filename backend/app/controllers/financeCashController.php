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

}