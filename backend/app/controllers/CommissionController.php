<?php

class CommissionController extends BaseController
{
    public $zone = '';
    public $date1 = '';
    public $date2 = '';

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

            $invoiceQ = [];
            $invoice = Invoice::whereBetween('deliveryDate', [$this->date1, $this->date2])->get();
            foreach ($invoice as $invoiceQ) {
                if ($invoiceQ->invoiceStatus == '98') {
                    if ($invoiceQ->paymentTerms == 2) {
                        $this->_sumcredit -= $invoiceQ->amount;
                    } else {
                        $this->_sumcod -= $invoiceQ->amount;
                    }
                }else{
                    if ($invoiceQ->paymentTerms == 2) {
                        $this->_sumcredit += $invoiceQ->amount;
                        $this->_countcredit += 1;
                    } else {
                        $this->_sumcod += $invoiceQ->amount;
                        if ($invoiceQ->invoiceStatus == '96')
                            $this->_countcodreplace += 1;
                        else if($invoiceQ->invoiceStatus == '97')
                            $this->_countcodreplenishment += 1;
                        else
                            $this->_countcod += 1;
                    }
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

        $csv = '車號,'.$this->zone. "\r\n";
        $csv .= '日期,'.date('Y-m-d',$this->date1).',至,'.date('Y-m-d',$this->date2). "\r\n";
        $csv .= 'Product ID,Name,Total Qty,Unit' . "\r\n";
        foreach ($invoices as $item) {
            if($item['productQtys'] != false){
                $csv .= '"' . $item['productId'] . '",';
                $csv .= '"' . $item['productName_chi'] . '",';
                $csv .= '"' . $item['productPackingName_carton'] . '",';
                $csv .= '"' . $item['productQtys'] . '",';
                $csv .= "\r\n";
            }
        }

        $csv .= '"現金總數:",';
        $csv .= '"' . $summary['countcod'] . '",';
        $csv .= '"' . $summary['sumcod'] . '",';
        $csv .= '"",';
        $csv .= "\r\n";

        $csv .= '"月結總數:",';
        $csv .= '"' . $summary['countcredit'] . '",';
        $csv .= '"' . $summary['sumcredit'] . '",';
        $csv .= '"",';
        $csv .= "\r\n";

        $csv .= '"退貨單:",';
        $csv .= '"' . $summary['countcodreturn'] . '",';
        $csv .= '"",';
        $csv .= '"",';
        $csv .= "\r\n";

        $csv .= '"換貨單:",';
        $csv .= '"' . $summary['countcodreplace'] . '",';
        $csv .= '"",';
        $csv .= '"",';
        $csv .= "\r\n";

        $csv .= '"補貨單:",';
        $csv .= '"' . $summary['countcodreplenishment'] . '",';
        $csv .= '"",';
        $csv .= '"",';
        $csv .= "\r\n";

        echo "\xEF\xBB\xBF";
        $headers = array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Commission.csv"',
        );

        return Response::make(rtrim($csv, "\n"), 200, $headers);

    }

}