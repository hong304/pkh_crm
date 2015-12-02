<?php

class arrivedContainerController extends BaseController{
    
    public function jqueryGetArrived()
    {
       $mode = Input :: get('mode');
       $filterData = Input :: get('filterData');
               $receiving = Receiving ::select('receivings.containerId','supplier_unitName','shippings.bl_number','shippings.vessel','shippings.shipCompany','shippings.actualDate','shippings.fsp','suppliers.supplierName','receivings.rec_receiveQty','receivings.supplier_unitName','shippingitems.container_size','product.productName_chi','shippingitems.container_receiveDate','shippings.etaDate','receivings.unit_cost')
                       ->whereNull('adjustId')
                       ->where('receivings.containerId','!=','')
                       ->join('purchaseorders', 'receivings.poCode', '=', 'purchaseorders.poCode')
                          ->leftJoin('shippings', function($join) {
                             $join->on('shippings.shippingId', '=', 'receivings.shippingId');
                          })
                          ->leftJoin('shippingitems', function($joins) {
                             $joins->on('shippingitems.containerId', '=', 'receivings.containerId');
                          })
                          ->leftJoin('product', function($join) {
                             $join->on('product.productId', '=', 'receivings.productId');
                          })    
                          ->leftJoin('suppliers', function($join) {
                             $join->on('purchaseorders.supplierCode', '=', 'suppliers.supplierCode');
                          })
                           ->where('suppliers.location', '=' ,2)
                           ->where('purchaseorders.poStatus','!=',99)
                           ->orderby('shippings.actualDate','desc');
                        
             if($mode == "arrivedContainer")
             {
                 $receiving->where(function ($query) use ($filterData) {
                            if (isset($filterData['containerId'] ) && $filterData['containerId'] != "")
                                $query->where('receivings.containerId', 'LIKE', '%' . $filterData['containerId'] . '%');
                            if (isset($filterData['actualDateStart']) && $filterData['actualDateStart'] != "" && isset($filterData['actualDateEnd']) &&$filterData['actualDateEnd'] != "")
                                $query->whereBetween('shippings.actualDate', [$filterData['actualDateStart'],  $filterData['actualDateEnd']]);
                          });
                return Datatables::of($receiving)
                ->editColumn('fspDate', function($receivi) {
                    if($receivi->actualDate != "" && isset($receivi->actualDate))
                    {
                        $fspDate = date('Y-m-d',strtotime($receivi->actualDate) + $receivi->fsp * 24 * 60 * 60);
                        return $fspDate;
                    }
                }) 
                ->editColumn('rec_receiveQty', function($receivi) {
                    return $receivi->rec_receiveQty . "(" .$receivi->supplier_unitName . ")";
                }) 
                ->make(true);
             }else if($mode == "vensum")
             {
                  $receiving->where(function ($query) use ($filterData) {
                            if (isset($filterData['bl_number'] ) && $filterData['bl_number'] != "")
                                $query->where('shippings.bl_number', 'LIKE', '%' . $filterData['bl_number'] . '%');
                            if (isset($filterData['etaDateStart']) && $filterData['etaDateStart'] != "" && isset($filterData['etaDateEnd']) &&$filterData['etaDateEnd'] != "")
                                $query->whereBetween('shippings.etaDate', [$filterData['etaDateStart'],  $filterData['etaDateEnd']]);
                          });
                       
                return Datatables::of($receiving)
                ->editColumn('multiple', function($receivi) {
                    return '$'.$receivi->unit_cost * $receivi->rec_receiveQty;
                }) 
                ->editColumn('totalPrice', function($receivi) {
                    return "";
                }) 
                ->editColumn('unitprice', function($receivi) {
                    return "$".$receivi->unit_cost;
                }) 
                 ->editColumn('brand', function($receivi) {
                    return "";
                }) 
                 ->editColumn('rec_receiveQty', function($receivi) {
                    return $receivi->rec_receiveQty . "(" .$receivi->supplier_unitName . ")";
                })
                ->make(true);
             }
            
               return Response::json($receiving);
    }
   
}