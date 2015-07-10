<?php

class CommissionController extends BaseController
{

    public function queryCommission()
    {

        $filter = Input::get('filterData');


        $zone = (isset($filter['zone']['zoneId'])) ? $filter['zone']['zoneId'] : '0';
        $data1 = (isset($filter['deliveryDate']) ? strtotime($filter['deliveryDate']) : strtotime("today"));
        $data2 = (isset($filter['deliveryDate1']) ? strtotime($filter['deliveryDate1']) : strtotime("today"));

        $invoices = Invoice::select(DB::raw('SUM(productQty) AS productQtys'), 'productName_chi', 'InvoiceItem.productId', 'productUnitName', 'productQtyUnit', 'productPacking_carton', 'productPacking_inner', 'productPacking_unit', 'productPackingName_carton')->leftJoin('InvoiceItem', function ($join) {
            $join->on('Invoice.invoiceId', '=', 'InvoiceItem.invoiceId');
        })->leftJoin('Product', function ($join) {
            $join->on('InvoiceItem.productId', '=', 'Product.productId');
        })->whereNotIn('invoiceStatus',['96','95','98'])->groupBy('InvoiceItem.productId')->groupBy('productQtyUnit')
        ->where('InvoiceItem.productPrice','!=',0);
        $invoices->where('zoneId', $zone);
        $invoices->whereBetween('Invoice.deliveryDate', [$data1, $data2]);


        $invoice_return = Invoice::select(DB::raw('SUM(productQty) AS productQtys'), 'productName_chi', 'InvoiceItem.productId', 'productUnitName', 'productQtyUnit', 'productPacking_carton', 'productPacking_inner', 'productPacking_unit', 'productPackingName_carton')->leftJoin('InvoiceItem', function ($join) {
            $join->on('Invoice.invoiceId', '=', 'InvoiceItem.invoiceId');
        })->leftJoin('Product', function ($join) {
            $join->on('InvoiceItem.productId', '=', 'Product.productId');
        })->where('invoiceStatus','98')->groupBy('InvoiceItem.productId')->groupBy('productQtyUnit')
            ->where('zoneId', $zone)
            ->whereBetween('Invoice.deliveryDate', [$data1, $data2])->get();

        foreach($invoices as $invoiceQ)
        {
            foreach($invoice_return as $g){
                if($g->productId == $invoiceQ->productId && $g->productQtyUnit == $invoiceQ->productQtyUnit ){
                    $invoiceQ->productQtys -= $g->productQtys;
                }
            }
        }

        if (Input::get('mode') == 'csv') {
            $invoices = $invoices->get()->toArray();
//pd($invoices);
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
            }
  
            return $this->exportCsv($a);
        } else {
            Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);
            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
            $invoices = $invoices->paginate($page_length);

        }



        return Response::json($invoices);

    }

    public function exportCsv($invoices)
    {

        $csv = 'Product ID,Name,Total Qty,Unit' . "\r\n";
        foreach ($invoices as $item) {
            if($item[0]['productQtyUnit_final'] != false){
                $csv .= '"' . $item[0]['productId'] . '",';
                $csv .= '"' . $item[0]['productName_chi'] . '",';
                $csv .= '"' . $item[0]['productQtyUnit_final'] . '",';
                $csv .= '"' . $item[0]['productPackingName_carton'] . '",';
                $csv .= "\r\n";
            }
        }
        echo "\xEF\xBB\xBF";
        $headers = array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Commission.csv"',
        );

        return Response::make(rtrim($csv, "\n"), 200, $headers);

    }

}