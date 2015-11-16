<?php

class arrivedContainerController extends BaseController{
    
    public function jqueryGetArrived()
    {
       $mode = Input :: get('mode');
       $filterData = Input :: get('filterData');
               $receiving = Receiving ::select('receivings.containerId','rec_receiveQty','supplier_unitName','shippings.vessel','shippings.shipCompany','product.productName_chi','shippings.actualDate','shippings.fsp','shippingitems.container_receiveDate','shippingitems.container_size','suppliers.supplierName')
                        ->join('purchaseorders', 'receivings.poCode', '=', 'purchaseorders.poCode')
                          ->leftJoin('shippings', function($join) {
                             $join->on('shippings.shippingId', '=', 'receivings.shippingId');
                          })
                          ->leftJoin('shippingitems', function($join) {
                             $join->on('shippingitems.shippingId', '=', 'receivings.shippingId');
                          })
                          ->leftJoin('product', function($join) {
                             $join->on('product.productId', '=', 'receivings.productId');
                          })    
                          ->leftJoin('suppliers', function($join) {
                             $join->on('purchaseorders.supplierCode', '=', 'suppliers.supplierCode');
                          });
              return Datatables::of($receiving)
                       ->editColumn('fspDate', function($receivi) {
                                $fspDate = date('Y-m-d',strtotime($receivi->actualDate) + $receivi->fsp * 24 * 60 * 60);
                                return $fspDate;
                            })                    
              ->make(true);
               return Response::json($receiving);
    }
   
}