<?php

class financialReportController extends BaseController
{

    private $_date,$_unPaid = '';

    public function getYearEndReport(){

        ini_set('memory_limit', '-1');


        if (Input::get('mode') == 'csv') {

            $ymd = Input::get('filterData.deliveryDate');
            $m = date("m", strtotime(Input::get('filterData.deliveryDate')));
            $y = date("Y", strtotime(Input::get('filterData.deliveryDate')));

            $time_interval = [['0', '0'], ['1', '1'], ['2', '2'], ['3', '3'], ['4', '4'], ['5', '5'], ['6', '6'], ['7', '7'], ['8', '8'], ['9', '9'], ['10', '10'], ['11', '11']];
           // $dateRange = ['C', 'D', 'E', 'F', 'G', 'H','I'];

            $first = true;



            foreach ($time_interval as $v) {
                if ($first) {
                    $time[date("Y-m", strtotime($ymd . "-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime($ymd . "-" . $v[0] . " month"));
                    $time[date("Y-m", strtotime($ymd . "-" . $v[1] . " month"))][1] = date("Y-m-d", strtotime($ymd . "-" . $v[1] . " month"));
                    $first = false;
                } else {
                    $time[date("Y-m", mktime(0, 0, 0, $m-$v[0], 1, $y))][0] = date("Y-m-01", mktime(0, 0, 0, $m-$v[0], 1, $y));
                    $time[date("Y-m", mktime(0, 0, 0, $m-$v[1], 1, $y))][1] = date("Y-m-t", mktime(0, 0, 0, $m-$v[1], 1, $y));
                }
            }



            $month[0] = key(array_slice($time, -12, 1, true));
            $month[1] = key(array_slice($time, -11, 1, true));
            $month[2] = key(array_slice($time, -10, 1, true));
            $month[3] = key(array_slice($time, -9, 1, true));
            $month[4] = key(array_slice($time, -8, 1, true));
            $month[5] = key(array_slice($time, -7, 1, true));
            $month[6] = key(array_slice($time, -6, 1, true));
            $month[7] = key(array_slice($time, -5, 1, true));
            $month[8] = key(array_slice($time, -4, 1, true));
            $month[9] = key(array_slice($time, -3, 1, true));
            $month[10] = key(array_slice($time, -2, 1, true));
            $month[11] = key(array_slice($time, -1, 1, true));


            //  $total = 0;

                   foreach ($time as $k => $v) {
                       $sql = 'SELECT SUM(CASE WHEN invoiceStatus IN (20,30) THEN (paid+discount_taken) END) AS settlement, SUM(CASE WHEN invoiceStatus IN (20,30) THEN (discount_taken) END) AS discount,SUM(amount) AS amount,COUNT(*) AS invoices FROM invoice WHERE paymentterms = 2 AND invoiceStatus IN (2,20,30) AND deliveryDate between '.strtotime($v[0]).' AND '.strtotime($v[1]);
                       $data2[$k] = DB::select(DB::raw($sql));

                       $sql = 'SELECT SUM(CASE WHEN invoiceStatus = 20 THEN paid WHEN invoiceStatus = 30 AND manual_complete = 0 THEN (paid+discount_taken) ELSE amount END) AS settlement, SUM(CASE WHEN invoiceStatus =30 THEN (discount_taken) END) AS discount,SUM(amount) AS amount,COUNT(*) AS invoices FROM invoice WHERE paymentterms = 1 AND invoiceStatus IN (2,20,30) AND deliveryDate between '.strtotime($v[0]).' AND '.strtotime($v[1]);
                       $data[$k] = DB::select(DB::raw($sql));

                     }



        }



      //  $value = debug::where('id',1)->first();
      //  $this->_monthly = json_decode($value->content);
     // pd($data[''.$k.''][0]->invoices);

        $i = 6;
        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';


        $objPHPExcel = new PHPExcel();


        $objPHPExcel->getActiveSheet()->setCellValue('A1', '炳 記 行 貿 易 有 限 公 司');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');
        $objPHPExcel->getActiveSheet()->setCellValue('A2', 'Monthly Sales Status');
        $objPHPExcel->getActiveSheet()->mergeCells('A2:E2');
        $objPHPExcel->getActiveSheet()->setCellValue('A3', '至 [' . $ymd . "]");
        $objPHPExcel->getActiveSheet()->mergeCells('A3:C3');

        $objPHPExcel->getActiveSheet()->mergeCells('C4:G4');
        $objPHPExcel->getActiveSheet()->setCellValue('C4', 'Credit Sales');
        $objPHPExcel->getActiveSheet()->mergeCells('I4:M4');
        $objPHPExcel->getActiveSheet()->setCellValue('I4', 'Cash Sales');

        $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, "月份");

        $objPHPExcel->getActiveSheet()->setCellValue('C' . $i,'No. of invoices');
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, 'Sales Amount');
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, 'Settlement');
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, 'Discount');
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, 'Accumulated outstanding as at end of the month');

        $objPHPExcel->getActiveSheet()->setCellValue('I' . $i,'No. of invoices');
        $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, 'Sales Amount');
        $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, 'Receipts');
        $objPHPExcel->getActiveSheet()->setCellValue('L' . $i, 'Discount');
        $objPHPExcel->getActiveSheet()->setCellValue('M' . $i, 'Accumulated outstanding as at end of the month');



        $j = $i + 1;
        foreach ($month as $k => $v) {

            $objPHPExcel->getActiveSheet()->setCellValue('A' . $j, $v);

            $objPHPExcel->getActiveSheet()->setCellValue('C' . $j, $data2[''.$v.''][0]->invoices);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $j, $data2[''.$v.''][0]->amount);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $j, $data2[''.$v.''][0]->settlement);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $j, $data2[''.$v.''][0]->discount);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $j, "=D" . $j . "-E" . $j. "-F" . $j);

            $objPHPExcel->getActiveSheet()->setCellValue('I' . $j, $data[''.$v.''][0]->invoices);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $j, $data[''.$v.''][0]->amount);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $j, $data[''.$v.''][0]->settlement);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $j, $data[''.$v.''][0]->discount);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $j, "=J" . $j . "-K" . $j. "-L" . $j);

            $j++;
        }


        $objPHPExcel->getActiveSheet()
            ->getStyle('C7:M18')
            ->getNumberFormat()
            ->setFormatCode(
                '$#,##0.00;[Red]$#,##0.00'
            );

        foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
            // $calculatedWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($col)->getWidth();
            $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . "Monthly_sales_status" .$ymd. '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');



    }

    public function getAgingByZoneCash()
    {

        ini_set('memory_limit', '-1');


        if (Input::get('mode') == 'csv') {

            $ymd = Input::get('filterData.deliveryDate');


            $time_interval = [['0', '0'], ['1', '1'], ['2', '2'], ['5', '3'], ['11', '6'], ['120', '12']];
            $dateRange = ['C', 'D', 'E', 'F', 'G', 'H','I'];

            $first = true;

            foreach ($time_interval as $v) {
                if ($first) {
                    $time[date("Y-m", strtotime($ymd . "-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime($ymd . "-" . $v[0] . " month"));
                    $time[date("Y-m", strtotime($ymd . "-" . $v[1] . " month"))][1] = date("Y-m-d", strtotime($ymd . "-" . $v[1] . " month"));
                    $first = false;
                } else {
                    $time[date("Y-m", strtotime($ymd . "-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime($ymd . "-" . $v[0] . " month"));
                    $time[date("Y-m", strtotime($ymd . "-" . $v[1] . " month"))][1] = date("Y-m-t", strtotime($ymd . "-" . $v[1] . " month"));
                }
            }


            $month[0] = key(array_slice($time, -6, 1, true));
            $month[1] = key(array_slice($time, -5, 1, true));
            $month[2] = key(array_slice($time, -4, 1, true));
            $month[3] = key(array_slice($time, -3, 1, true));
            $month[4] = key(array_slice($time, -2, 1, true));
            $month[5] = key(array_slice($time, -1, 1, true));


            //  $total = 0;
            for ($i = 0; $i < 23; $i++) {

                //for ($i = $this->_reportMonth; $i > 0; $i--) {
                $data = [];

                foreach ($time as $k => $v) {
                    $data[$k] = Invoice::whereBetween('deliveryDate', [strtotime($v[0]), strtotime($v[1])])->where('paymentTerms', 1)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->whereNotIn('invoiceStatus', ['2', '30', '99'])->where('zoneId', $i)->OrderBy('deliveryDate')->get();

                    foreach ($data[$k] as $invoice) {

                        if (!isset($this->_monthly[$i][$k]))
                            $this->_monthly[$i][$k] = 0;

                        $this->_monthly[$i][$k] += ($invoice->amount - ($invoice->paid + $invoice->discount_taken));
                        // $total += ($invoice->realAmount - ($invoice->paid + $invoice->discount_taken));

                    }
                }

            }

        }

      //  $value = debug::where('id',1)->first();
     //   $this->_monthly = json_decode($value->content);


        $i = 5;
        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';


        $objPHPExcel = new PHPExcel();


        $objPHPExcel->getActiveSheet()->setCellValue('A1', '炳 記 行 貿 易 有 限 公 司');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');
        $objPHPExcel->getActiveSheet()->setCellValue('A2', '帳齡分析搞要(應收)');
        $objPHPExcel->getActiveSheet()->mergeCells('A2:E2');
        $objPHPExcel->getActiveSheet()->setCellValue('A3', '至 [' . $ymd . "]");
        $objPHPExcel->getActiveSheet()->mergeCells('A3:C3');

        $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, "車線");

        $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $month[0]);
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $month[1]);
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $month[2]);
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $month[3]);
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $month[4]);
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $month[5]);
        $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, "Total");


        $j = $i + 1;
        $storeRow = $j;


        foreach ($this->_monthly as $ks => $vs) {

            $objPHPExcel->getActiveSheet()->setCellValue('A' . $j, $ks);

             $yy = 0;


            foreach($month as $v){
                    if(isset($vs[$v])){
                        $objPHPExcel->getActiveSheet()->setCellValue($dateRange[$yy] . $j, $vs[$v]);
                    }
                $yy++;
            }

            $objPHPExcel->getActiveSheet()->setCellValue('I' . $j, "=SUM(C" . $j . ":H" . $j . ")");
             $j++;
        }

        $j++;



        $objPHPExcel->getActiveSheet()->setCellValue('A' . $j, "合共總額:");

        $hh = $j - 2;

        $styleArray = array(
            'font' => array(
                'underline' => PHPExcel_Style_Font::UNDERLINE_DOUBLE
            )
        );

        for ($count = 0; $count < count($dateRange); $count++) {
            $objPHPExcel->getActiveSheet()->setCellValue($dateRange[$count] . $j, "=SUM(" . $dateRange[$count] . $storeRow . ":" . $dateRange[$count] . $hh . ")");
            $objPHPExcel->getActiveSheet()->getStyle($dateRange[$count] . $j)->applyFromArray($styleArray);
        }

        unset($styleArray);


        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . "agingByZoneCash" .$ymd. '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');


    }

    public function getDailySalesSummary(){

        $this->_date = strtotime(Input::get('filterData.deliveryDate'));
        //expenses
        $expenses = expense::where('deliveryDate',date('Y-m-d',$this->_date))->get();
        foreach($expenses as $v){
            if(!isset($expenses_amount[$v->zoneId]))
                $expenses_amount[$v->zoneId] = 0;
            $expenses_amount[$v->zoneId] = $v->cost1+$v->cost2+$v->cost3+$v->cost4;
        }
        //expenses

        // pd($expenses_amount);
        //B/F
        $invoices = Invoice::where('paymentTerms',1)->where('deliveryDate','<',$this->_date)->where('invoiceStatus',20)->orderBy('zoneId')->get();
        $balance_bf = [];
        foreach($invoices as $v){

            if(!isset($balance_bf[$v->zoneId]))
                $balance_bf[$v->zoneId] = 0;
            $balance_bf[$v->zoneId] += $v->remain;
        }
        //B/F

        //補收
        $invoices = Invoice::where('paymentTerms',1)->whereIn('invoiceStatus',[20,30])->with('payment')
            ->leftJoin('invoice_payment', function ($join) {
                $join->on('invoice_payment.invoice_id', '=', 'Invoice.invoiceId');
            })->leftJoin('payments', function ($join) {
                $join->on('invoice_payment.payment_id', '=', 'payments.id');
            })->where('receive_date', date('Y-m-d',$this->_date))->get();

        foreach($invoices as $invoiceQ){
            foreach($invoiceQ->payment as $v1){
                if($v1->receive_date == date('Y-m-d',$this->_date) and $invoiceQ->deliveryDate < $this->_date ){
                    $previous[$invoiceQ->zoneId] = (isset($previous[$invoiceQ->zoneId]))?$previous[$invoiceQ->zoneId]:0;
                    $previous[$invoiceQ->zoneId] += $v1->pivot->paid;
                }
            }
        }
        //補收

        //當天單,不是當天收錢
        /*   $invoicesQuery = Invoice::select('invoiceId','invoice_payment.paid')->whereIn('invoiceStatus',['1','2','20','30','98','97','96'])->where('paymentTerms',1);
           if($this->_shift != '-1')
               $invoicesQuery->where('shift',$this->_shift);
           $invoicesQuery = $invoicesQuery->leftJoin('invoice_payment', function ($join) {
               $join->on('invoice_payment.invoice_id', '=', 'Invoice.invoiceId');
           })->leftJoin('payments', function ($join) {
               $join->on('invoice_payment.payment_id', '=', 'payments.id');
           })->where('deliveryDate', '=', $this->_date)
               ->where(function ($query) {
                   $query->where('receive_date', '!=', date('y-m-d',$this->_date));
               })->get();

           $uncheque = [];
           foreach($invoicesQuery as $v){
               if(!isset($uncheque[$v->invoiceId]))
                   $uncheque[$v->invoiceId] = 0;
               $uncheque[$v->invoiceId] += $v->paid;
           }*/
        //當天單,不是當天收錢


        $invoices = Invoice::whereIn('invoiceStatus',['2','20','30','98'])->where('deliveryDate',$this->_date)->get();


        $NoOfInvoices = [];
        foreach ($invoices as $invoiceQ){

            if($invoiceQ->paymentTerms == 1){
                if(!isset($NoOfInvoices[$invoiceQ->paymentTerms][$invoiceQ->zoneId]))
                    $NoOfInvoices[$invoiceQ->paymentTerms][$invoiceQ->zoneId] = 0;

                $NoOfInvoices[$invoiceQ->paymentTerms][$invoiceQ->zoneId] += 1;

                if(!isset( $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId]['totalAmount']))
                    $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId]['totalAmount'] = 0;
                if(!isset( $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId]['receiveTodaySales']))
                    $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId]['receiveTodaySales'] = 0;

                if ($invoiceQ->invoiceStatus == 30 || $invoiceQ->invoiceStatus == 20){
                    // $paid = $invoiceQ->paid - ( isset($uncheque[$invoiceQ->invoiceId])?$uncheque[$invoiceQ->invoiceId]:0 );
                    $paid = $invoiceQ->paid+$invoiceQ->discount_taken;
                }else{
                    $paid = $invoiceQ->amount;
                }

                $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId] = [
                    'truck' => $invoiceQ->zoneId,
                    'noOfInvoices' => $NoOfInvoices[$invoiceQ->paymentTerms][$invoiceQ->zoneId],
                    'balanceBf' => isset($balance_bf[$invoiceQ->zoneId])?$balance_bf[$invoiceQ->zoneId]:0,
                    'totalAmount' => $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId]['totalAmount'] += (($invoiceQ->invoiceStatus == '98')? -$invoiceQ->amount:$invoiceQ->amount),
                    'previous'=>isset($previous[$invoiceQ->zoneId])?$previous[$invoiceQ->zoneId]:0,
                    'receiveTodaySales' => $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId]['receiveTodaySales'] += (($invoiceQ->invoiceStatus == '98')? -$paid:$paid),
                    'expenses' =>  isset($expenses_amount[$invoiceQ->zoneId])?$expenses_amount[$invoiceQ->zoneId]:0,
                ];
            }else if($invoiceQ->paymentTerms == 2){

                if(!isset($NoOfInvoices[$invoiceQ->paymentTerms][$invoiceQ->zoneId]))
                    $NoOfInvoices[$invoiceQ->paymentTerms][$invoiceQ->zoneId] = 0;

                $NoOfInvoices[$invoiceQ->paymentTerms][$invoiceQ->zoneId] += 1;

                if(!isset( $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId]['totalAmount']))
                    $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId]['totalAmount'] = 0;

                $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId] = [
                    'noOfInvoices' => $NoOfInvoices[$invoiceQ->paymentTerms][$invoiceQ->zoneId],
                    'totalAmount' => $info[$invoiceQ->paymentTerms][$invoiceQ->zoneId]['totalAmount'] += (($invoiceQ->invoiceStatus == '98')? -$invoiceQ->amount:$invoiceQ->amount),
                ];

            }
        }


        ksort($info[1]);
        ksort($info[2]);


        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';

        $i=6;
        $objPHPExcel = new PHPExcel ();


        $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Ping Kee Hong Trading Company Limited');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:C1');
        $objPHPExcel->getActiveSheet()->setCellValue('A2', 'Daily sales summary');
        $objPHPExcel->getActiveSheet()->mergeCells('A2:C2');

        $objPHPExcel->getActiveSheet()->setCellValue('A3', 'As at');
        $objPHPExcel->getActiveSheet()->setCellValue('B3', date('Y-m-d',$this->_date));

        $objPHPExcel->getActiveSheet()->setCellValue('A4', 'Cash Sales');
        $objPHPExcel->getActiveSheet()->mergeCells('A4:I4');
        $objPHPExcel->getActiveSheet()->getStyle('A4')->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,)
        );

        $objPHPExcel->getActiveSheet()->setCellValue('J4', 'Credit Sales');
        $objPHPExcel->getActiveSheet()->mergeCells('J4:K4');
        $objPHPExcel->getActiveSheet()->getStyle('J4')->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,)
        );

        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, 'Truck');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, 'No. of invoices');

        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, 'Today sales');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, 'Receive for today sales');
        $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, 'Receive for previous sales');
        $objPHPExcel->getActiveSheet()->setCellValue('F'.$i, 'Cash disbursements');
        $objPHPExcel->getActiveSheet()->setCellValue('G'.$i, 'Net receipt');

        $objPHPExcel->getActiveSheet()->setCellValue('H'.$i, 'Balance B/F');
        $objPHPExcel->getActiveSheet()->setCellValue('I'.$i, 'Balance C/F');

        $objPHPExcel->getActiveSheet()->setCellValue('J'.$i, 'No. of invoices');
        $objPHPExcel->getActiveSheet()->setCellValue('K'.$i, 'Today sales');

        $j = $i+ 1;



        foreach ($info[1] as $k => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $j, $v['truck']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $j, $v['noOfInvoices']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $j, $v['totalAmount']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $j, $v['receiveTodaySales']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $j, $v['previous']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $j, $v['expenses']*-1);

            $objPHPExcel->getActiveSheet()->setCellValue('G' . $j, '=D'.$j.'+E'.$j.'+F'.$j);

            $objPHPExcel->getActiveSheet()->setCellValue('H' . $j, $v['balanceBf']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $j, '=H'.$j.'+C'.$j.'-E'.$j.'-D'.$j);
            $j++;
        }

        $j = $i+ 1;
        foreach($info[2] as $k => $v){
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $j, $v['noOfInvoices']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $j, $v['totalAmount']);
            $j++;
        }




        foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
            // $calculatedWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($col)->getWidth();
            $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }

        $k = $i+1;
        $objPHPExcel->getActiveSheet()
            ->getStyle('C'.$k.':I35')
            ->getNumberFormat()
            ->setFormatCode(
                '$#,##0.00;[Red]$#,##0.00'
            );

        $objPHPExcel->getActiveSheet()
            ->getStyle('K'.$k.':K35')
            ->getNumberFormat()
            ->setFormatCode(
                '$#,##0.00;[Red]$#,##0.00'
            );

        $l = ['B','C','D','E','F','G','H','I','J','K'];
        foreach($l as $zz){
            $objPHPExcel->getActiveSheet()->setCellValue($zz.'31','=SUM('.$zz.$k.':'.$zz.'29)');
        }

        // $objPHPExcel->getActiveSheet()->SetCellValue('B31', "=SUM(B6:B29");

//        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.date('Ymd',$this->_date).'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }
    
    public function outputCashAndCredit()
    {
        $filter = Input::get('filterData');
        $this->_date1 = (isset($filter['datepicker1']) ? strtotime($filter['datepicker1']) : strtotime("today"));
        $this->_date2 = (isset($filter['datepicker2'])) ? strtotime($filter['datepicker2']) : strtotime("today");

            if ($filter['groupName'] == '' && $filter['name'] == '' && $filter['phone'] == '' && $filter['customerId'] == '') {
            $empty = true;
            $this->data = [];
            } else {
            $empty = false;
            }
 
        
        if (!$empty) {
              $invoices = Invoice::leftJoin('Customer', function($join) {
                    $join->on('Customer.customerId', '=', 'Invoice.customerId');
               })->leftJoin('customer_groups', function($join) {
                    $join->on('customer_groups.id', '=', 'Customer.customer_group_id');
               })->where('Invoice.deliveryDate', '<=', $this->_date2);
         
               if($filter['groupName'] != "")
                   $invoices->where('customer_groups.name', 'LIKE', $filter['groupName']. '%');
               
               if ($filter['name'] != '' || $filter['phone'] != '' || $filter['customerId'] != '') {
                   $invoices->where(function ($query) use ($filter) {
                    $query
                            ->where('customerName_chi', 'LIKE', $filter['name'] . '%')
                            ->where('Customer.phone_1', 'LIKE', $filter['phone'] . '%')
                            ->where('Customer.customerId', 'LIKE', $filter['customerId'] . '%');
                    });
                }
            if($filter['paymentTerm'] == 1)
                $invoices = $invoices->where('paymentTerms', $filter['paymentTerm'])->where('invoiceStatus',20)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->OrderBy('invoice.customerId', 'asc')->orderBy('deliveryDate')->get();
            else
                $invoices = $invoices->where('paymentTerms', $filter['paymentTerm'])->where('invoiceStatus','!=',30)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->OrderBy('invoice.customerId', 'asc')->orderBy('deliveryDate')->get();
              foreach ($invoices as $invoice) {
                if ($invoice->deliveryDate >= $this->_date1) {
                    $customerId = $invoice->customerId;

                    $this->_unPaid[$customerId]['customer'] = [
                        'customerId' => $customerId,
                        'customerName' => $invoice->customerName_chi,
                    ];
                }
              }
              $this->data = $this->_unPaid;
             $this->outputSalesSummaryExcel($this->data,$filter['paymentTerm']);
        }else echo "這查詢沒有資料";
    }
    
    
    public function outputSalesSummaryExcel($dataInput,$paymentTerms) {
        
        $time_interval = [['0', '0'], ['1', '1'], ['2', '2'], ['5', '3'], ['11', '6'], ['120', '12']];
        $dateRange = ['F', 'G', 'H', 'I', 'J', 'K'];

        $first = true;

        foreach ($time_interval as $v) {
            if ($first) {
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime("-" . $v[0] . " month"));
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][1] = date("Y-m-d", strtotime("-" . $v[1] . " month"));
                $first = false;
            } else {
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][0] = date("Y-m-01", strtotime("-" . $v[0] . " month"));
                $time[date("Y-m", strtotime("-" . $v[1] . " month"))][1] = date("Y-m-t", strtotime("-" . $v[1] . " month"));
            }
        }

        $month[0] = key(array_slice($time, -6, 1, true));
        $month[1] = key(array_slice($time, -5, 1, true));
        $month[2] = key(array_slice($time, -4, 1, true));
        $month[3] = key(array_slice($time, -3, 1, true));
        $month[4] = key(array_slice($time, -2, 1, true));
        $month[5] = key(array_slice($time, -1, 1, true));
        
        if($dataInput !== ""){
        foreach ($dataInput as $i => $v) {
            $storeDate[$i] = $v['customer']; 
        }
        if(isset($storeDate))
        {
        $total = 0;
        foreach ($storeDate as $kk => $client) {

            //for ($i = $this->_reportMonth; $i > 0; $i--) {
            $data = [];

            foreach ($time as $k => $v) {
                if($paymentTerms == 1)
                   $data[$k] = Invoice::whereBetween('deliveryDate', [strtotime($v[0]), strtotime($v[1])])->where('invoiceStatus',20)->where('paymentTerms', $paymentTerms)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->where('Invoice.customerId', $kk)->OrderBy('deliveryDate')->get();
                else
                   $data[$k] = Invoice::whereBetween('deliveryDate', [strtotime($v[0]), strtotime($v[1])])->where('invoiceStatus','!=',30)->where('paymentTerms', $paymentTerms)->where('amount', '!=', DB::raw('paid'))->where('manual_complete', false)->where('Invoice.customerId', $kk)->OrderBy('deliveryDate')->get();

                foreach ($data[$k] as $invoice) {
                    $customerId = $invoice->customerId;
                    $customerName = $invoice->customerName;
                    $deliveryZone = $invoice->zoneId;

                    if (!isset($this->_monthly[$k]['byCustomer'][$customerId]))
                        $this->_monthly[$k]['byCustomer'][$customerId] = 0;

                    $this->_monthly[$k]['byCustomer'][$customerId] += ($invoice->realAmount - ($invoice->paid + $invoice->discount_taken));
                    $total += ($invoice->realAmount - ($invoice->paid + $invoice->discount_taken));
                    $this->_monthly['id'][$customerId] = $customerId;
                    $this->_monthly['name'][$customerId] = $customerName;
                    $this->_monthly['diliveryZone'][$customerId] = $deliveryZone;
                }
            }
        }
        
     //   pd($this->_monthly);

        $i = 5;
        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';


        $objPHPExcel = new PHPExcel();
        $this->generateExcelHeader($objPHPExcel);
        $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, "Customer");  
        
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, "District");
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $month[0]);
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $month[1]);
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $month[2]);
        $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, $month[3]);
        $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, $month[4]);
        $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $month[5]);
        

        $j = $i + 1;
        $storeRow = $j;
        $total = 0;
        $a = $j;
        $b = $j;
        $c = $j;
        $d = $j;
        
      //  pd($this->_monthly);
        
        foreach ($this->_monthly as $ks => $vs) {
                
                foreach ($vs as $is => $vvs) {
                    if($ks == "id")
                    {
                        $objPHPExcel->getActiveSheet()->setCellValue('A' . $a, $vvs);
                        $a++;
                    }else if($ks == "name")
                    {
                        $objPHPExcel->getActiveSheet()->setCellValue('B' . $b, $vvs);
                        $b++;
                    }else if($ks == "diliveryZone")
                    {
                        $objPHPExcel->getActiveSheet()->setCellValue('E' . $d, $vvs);
                        $d++;
                    }else
                    {
                        $c = $j;
                        foreach ($vvs as $g => $k) {
                          //  $objPHPExcel->getActiveSheet()->setCellValue('A' . $j, $g);
                            
                            for ($yy = 0; $yy < count($dateRange); $yy++) {
                                if ($ks == $month[$yy]) {
                                    $objPHPExcel->getActiveSheet()->setCellValue($dateRange[$yy] . $c, $vs['byCustomer'][$g]);
                                    $c++;
                                }
                            }
                        }
                    }
                }
            // $j++;
        }
        for($loopNum = 0;$loopNum<count($storeDate);$loopNum++)
        {
              $objPHPExcel->getActiveSheet()->setCellValue('L' . $j,"=SUM(F".$j.":K".$j.")");
              $j++;      
        }
         $j++; 
         
         
        $objPHPExcel->getActiveSheet()->setCellValue('A' . $j, "Total all:(HK$):");
        
        $hh = $j -2;

        $styleArray = array(
            'font' => array(
                'underline' => PHPExcel_Style_Font::UNDERLINE_DOUBLE
            )
        );
        $dateRange[6] = "L";
        for($count = 0;$count < count($dateRange);$count++)
        {
            $objPHPExcel->getActiveSheet()->setCellValue($dateRange[$count].  $j,"=SUM(".$dateRange[$count].$storeRow.":".$dateRange[$count].$hh.")");
             $objPHPExcel->getActiveSheet()->getStyle($dateRange[$count].  $j)->applyFromArray($styleArray); 
        }

        unset($styleArray);
        for($start = 0;$start < count($dateRange);$start++)
        {
            $objPHPExcel->getActiveSheet()
            ->getStyle($dateRange[$start].$storeRow.':'.$dateRange[$start].$j)
            ->getNumberFormat()
            ->setFormatCode(
                '$#,##0.00;[Red]$#,##0.00'
            ); 
            $objPHPExcel->getActiveSheet()->getColumnDimension($dateRange[$start])->setWidth(15);
        }
       
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        if($paymentTerms == 1)
            $nameVariable = "Cash report";
        else if($paymentTerms == 2)
           $nameVariable = "Credit report";
            header('Content-Disposition: attachment;filename="' . $nameVariable . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
         }
        }else echo "這查詢沒有資料";
    }
    
    public function generateExcelHeader($excel) {
        $today = date("Y-m-d");
        $excel->getActiveSheet()->setCellValue('A1', 'PING KEE HONG TRADING COMPANY LTD.');
        $excel->getActiveSheet()->mergeCells('A1:F1');
        $excel->getActiveSheet()->setCellValue('A2', 'Accounts Receivable Aging Report(Cash sales)');
        $excel->getActiveSheet()->mergeCells('A2:F2');
        $excel->getActiveSheet()->setCellValue('A3', 'As at[' . $today . "]");
        $excel->getActiveSheet()->mergeCells('A3:D3');
    }

}