<?php

class CommissionController extends BaseController {

    public function queryCommission(){

        $filter = Input::get('filterData');



        $zone = (isset($filter['zone']['zoneId']))?$filter['zone']['zoneId']:'-1';
        $data1 = (isset($filter['deliveryDate']) ? strtotime($filter['deliveryDate']) : strtotime("today"));
        $data2 = (isset($filter['deliveryDate1']) ? strtotime($filter['deliveryDate1']) : strtotime("today"));

        $invoices =  Invoice::select(DB::raw('SUM(productQty) AS productQtys'),'productName_chi','InvoiceItem.productId','productUnitName')->leftJoin('InvoiceItem', function($join) {
            $join->on('Invoice.invoiceId', '=', 'InvoiceItem.invoiceId');
        }) ->leftJoin('Product', function($join) {
            $join->on('InvoiceItem.productId', '=', 'Product.productId');
        }) ->groupBy('InvoiceItem.productId')->groupBy('productQtyUnit');

        if($zone != '-1')
            $invoices-> where('zoneId', $zone);
        else
            $invoices-> wherein('zoneId', explode(',', Auth::user()->temp_zone));

        $invoices->whereBetween('Invoice.deliveryDate', [$data1,$data2]);


        if(Input::get('mode') == 'csv') {
            $invoices = $invoices->get();
           return $this->exportCsv($invoices);
        }  else {
            Paginator::setCurrentPage(Input::get('start') / Input::get('length') + 1);
            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
            $invoices = $invoices->paginate($page_length);

        }
        return Response::json($invoices);

    }

    public function exportCsv($invoices){

        $csv = 'Product ID,Name,Total Qty,Unit' . "\r\n";
        foreach ($invoices as $item) {
            $csv .= '"' . $item->productId . '",';
            $csv .= '"' . $item->productName_chi . '",';
            $csv .= '"' . $item->productQtys . '",';
            $csv .= '"' . $item->productUnitName . '",';
            $csv .= "\r\n";
        }
        echo "\xEF\xBB\xBF";
        $headers = array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="ExportFileName.csv"',
        );

        return Response::make(rtrim($csv, "\n"), 200, $headers);

    }

}