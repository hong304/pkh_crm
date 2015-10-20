<?php

class financialReportController extends BaseController
{

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

        $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, "車線");

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

            $objPHPExcel->getActiveSheet()->setCellValue('C' . $j, $data[''.$v.''][0]->invoices);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $j, $data[''.$v.''][0]->amount);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $j, $data[''.$v.''][0]->settlement);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $j, $data[''.$v.''][0]->discount);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $j, "=D" . $j . "-E" . $j. "-F" . $j);

            $objPHPExcel->getActiveSheet()->setCellValue('I' . $j, $data2[''.$v.''][0]->invoices);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $j, $data2[''.$v.''][0]->amount);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $j, $data2[''.$v.''][0]->settlement);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $j, $data2[''.$v.''][0]->discount);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $j, "=J" . $j . "-K" . $j. "-L" . $j);

            $j++;
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
         /*   for ($i = 0; $i < 23; $i++) {

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

            } */

        }

        $value = debug::where('id',1)->first();
        $this->_monthly = json_decode($value->content);


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
                    if(isset($vs->$v)){
                        $objPHPExcel->getActiveSheet()->setCellValue($dateRange[$yy] . $j, $vs->$v);
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

}