<?php

class financialReportController extends BaseController
{

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