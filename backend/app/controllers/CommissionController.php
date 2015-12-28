<?php

class CommissionController extends BaseController
{
    public $zone = '';
    public $date1 = '';
    public $date2 = '';


    private $_sumcredit = 0;
    private $_sumcod = 0;
    private $_countcredit = 0;
    private $_countcod = 0;
    private $_countcodreturn = 0;
    private $_countcodreplace = 0;
    private $_countcodreplenishment = 0;

    public function __construct(){

        if(!Auth::user()->can('view_commission')){
            pd('Permission Denied');
        }

        $filter = Input::get('filterData');
        $this->zone = (isset($filter['zone']['zoneId'])) ? $filter['zone']['zoneId'] : '0';
        $this->date1 = (isset($filter['deliveryDate']) ? strtotime($filter['deliveryDate']) : strtotime("today"));
        $this->date2 = (isset($filter['deliveryDate1']) ? strtotime($filter['deliveryDate1']) : strtotime("today"));
    }

    public function queryCommission()
    {

        ini_set('memory_limit', '-1');



       /* $invoice_return = Invoice::select(DB::raw('SUM(productQty) AS productQtys'), 'productName_chi', 'InvoiceItem.productId', 'productUnitName', 'productQtyUnit', 'productPacking_carton', 'productPacking_inner', 'productPacking_unit', 'productPackingName_carton')->leftJoin('InvoiceItem', function ($join) {
            $join->on('Invoice.invoiceId', '=', 'InvoiceItem.invoiceId');
        })->leftJoin('Product', function ($join) {
            $join->on('InvoiceItem.productId', '=', 'Product.productId');
        })->where('invoiceStatus','98')->groupBy('InvoiceItem.productId')->groupBy('productQtyUnit')
            ->where('zoneId', $this->zone)->where('hascommission',true)
            ->whereBetween('Invoice.deliveryDate', [$this->date1, $this->date2])->get();
         foreach($invoice_return as $g){

                    $invoiceQ[$g->productId] -= $g->productQtys;

            }*/

//pd($invoice_return);


        if (Input::get('mode') == 'csv') {

           /* $invoices = $invoices->toArray();
            foreach ($invoices as &$v) {

                $carton = ($v['productPacking_carton'] == false) ? 1:$v['productPacking_carton'];
                $inner = ($v['productPacking_inner']==false) ? 1:$v['productPacking_inner'];
                $unit = ($v['productPacking_unit'] == false) ? 1 : $v['productPacking_unit'];

                if ($v['productQtyUnit'] == 'carton') {
                    $v['commissionUnit'] = $v['productQtys'];
                }

                if ($v['productQtyUnit'] == 'unit') {
                    $v['commissionUnit'] = $v['productQtys'] / ($carton*$inner*$unit);
                }

                if ($v['productQtyUnit'] == 'inner') {
                    $v['commissionUnit'] = ($v['productQtys']*$inner) / ($carton*$inner*$unit);
                }
            }
            $a = [];
            foreach ($invoices as $u) {
                $a[$u['productId']][] = $u;
            }

            foreach ($a as &$g) {
                $cc = 0;
                foreach ($g as $z) {
                    $cc += $z['commissionUnit'];
                }
                foreach ($g as &$h) {
                    $h['productQtyUnit_final'] = floor($cc);
                }
            }*/


            $invoiceQ = [];

            $invoices = invoiceitem::leftJoin('invoice', function ($join) {
                $join->on('Invoice.invoiceId', '=', 'InvoiceItem.invoiceId');
            })->leftJoin('Product', function ($join) {
                $join->on('InvoiceItem.productId', '=', 'Product.productId');
            })->whereNotIn('invoiceStatus',['96','99','97'])
                ->where('InvoiceItem.productPrice','!=',0)->where('zoneId', $this->zone)->where('hascommission',true)
                ->whereBetween('Invoice.deliveryDate', [$this->date1, $this->date2])->orderBy('invoiceitem.productId')->get();      // $invoices = invoiceitem::where('invoiceId','I1508-009113')->first();


            // pd($invoices->real_normalized_unit);

            foreach($invoices as $k => $v){
                $invoiceQ[$v->productId]['productId'] = $v->productId;
                $invoiceQ[$v->productId]['productName_chi'] = $v->productName_chi;

                if(!isset($invoiceQ[$v->productId]['normalizedQty'])){
                    $invoiceQ[$v->productId]['normalizedQty'] = 0;
                }

                $invoiceQ[$v->productId]['normalizedQty'] += $v->real_normalized_unit;

                $carton = ($v->productPacking_carton) ? $v->productPacking_carton:1;
                $inner = ($v->productPacking_inner) ? $v->productPacking_inner:1;
                $unit = ($v->productPacking_unit) ? $v->productPacking_unit:1;

                $invoiceQ[$v->productId]['normalizedUnit'] = $carton*$inner*$unit;
                $invoiceQ[$v->productId]['productPackingName_carton'] = $v->productPackingName_carton;
            }

            foreach($invoiceQ as &$vv){
                $vv['productQtys'] = floor($vv['normalizedQty']/$vv['normalizedUnit']);
            }

            $invoice = Invoice::whereBetween('deliveryDate', [$this->date1, $this->date2])->where('zoneId', $this->zone)->get();
            foreach ($invoice as $invoiceQ1) {
                   if ($invoiceQ1->paymentTerms == 2) {
                        $this->_sumcredit += $invoiceQ1->amount;
                        $this->_countcredit += 1;
                    } else {
                        $this->_sumcod += $invoiceQ1->amount;
                        if ($invoiceQ1->invoiceStatus == '96')
                            $this->_countcodreplace += 1;
                        else if($invoiceQ1->invoiceStatus == '97')
                            $this->_countcodreplenishment += 1;
                        else if($invoiceQ1->invoiceStatus == '98')
                            $this->_countcodreturn += 1;
                        else
                            $this->_countcod += 1;
                    }

            }

            $this->data['sumcredit'] = $this->_sumcredit;
            $this->data['sumcod'] = $this->_sumcod;
            $this->data['countcredit'] = $this->_countcredit;
            $this->data['countcod'] = $this->_countcod;

            $this->data['countcodreturn'] = $this->_countcodreturn;
            $this->data['countcodreplace'] = $this->_countcodreplace;
            $this->data['countcodreplenishment'] = $this->_countcodreplenishment;

            return $this->exportCsv($invoiceQ,$this->data);
        } else {
           // return Datatables::of($invoiceQ)->make(true);
        }



       // return Response::json($invoices);

    }

    public function exportCsv($invoices,$summary)
    {
   //     pd($invoices);


        require_once './Classes/PHPExcel/IOFactory.php';
        require_once './Classes/PHPExcel.php';


        $objPHPExcel = new PHPExcel ();
        $i=1;
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '佣金表');
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->applyFromArray(
            array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,)
        );

        $i += 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '車號:');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $this->zone);

        $i += 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '日期:');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, date('Y-m-d',$this->date1));
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, 'To');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, date('Y-m-d',$this->date2));

        $i += 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '產品編號');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, '產品名稱');
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, '總銷量');
        $objPHPExcel->getActiveSheet()->setCellValue('D'.$i, '單位');



        $i += 1;
        foreach ($invoices as $k => $v) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $v['productId']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $v['productName_chi']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $v['productQtys']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $v['productPackingName_carton']);
            $i++;

            $longest[] = strlen($v['productName_chi']);

        }

        $i += 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '現金總數:');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $summary['countcod'] );
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, '$' . number_format($summary['sumcod'],2,'.',','));

        $i += 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '月結總數:');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $summary['countcredit'] );
        $objPHPExcel->getActiveSheet()->setCellValue('C'.$i, '$' . number_format($summary['sumcredit'],2,'.',','));

        $i += 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '退貨單:');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $summary['countcodreturn'] );


        $i += 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '換貨單:');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $summary['countcodreplace'] );


        $i += 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '補貨單:');
        $objPHPExcel->getActiveSheet()->setCellValue('B'.$i, $summary['countcodreplenishment'] );



        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(max($longest));
        foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
            // $calculatedWidth = $objPHPExcel->getActiveSheet()->getColumnDimension($col)->getWidth();
            if($col != 'C')
                $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }


        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Commission-'.$this->zone.'-'.date('Ymd',$this->date1).'to'.date('Ymd',$this->date2).'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');


    }

}