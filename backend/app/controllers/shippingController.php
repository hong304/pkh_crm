<?php

class shippingController extends BaseController
{
    public function jsonSelectPo()
    {
        $purchase = array();
        $po = Input::get('input');
        $supplier = Input:: get('supplier');
        if($po !== "")
            $purchase = Purchaseorder::select('poCode','poDate','etaDate','purchaseorders.supplierCode','suppliers.supplierName')
                ->leftJoin('suppliers', function($join) {
                $join->on('suppliers.supplierCode', '=', 'purchaseorders.supplierCode');
                 })
                  ->where('purchaseorders.supplierCode',$po)
                 ->where('poStatus',1)
                         ->get();
         return Response::json($purchase);
    }
    
    public function newShipment()
    {
        $newShip = array();
        $shipment = Input:: get('ship');
        $shipItem = Input::get('product');
        $booleanval = isset ($shipment['shippingId']) ? $shipment['shippingId'] : false;
        $this->sh = new shippingMan($shipment['shippingId']);
        $this->sh->setShip($shipment);
        
        
         $have_item=false;    //fOR UPDATE
         $itemIds = [];
          foreach ($shipItem as $p) {
          if ($p['dbid'] != '' && $p['deleted'] == 0) 
               $itemIds[] = $p['dbid'];
          if ($p['dbid'] == '' && $p['containerId'] != '') 
                $have_item = true;
          } 

          
        //Below should be uncomment when the update function is ready
          if ($shipment['shippingId'] != '') {  //update
          if (count($itemIds) == 0 && !$have_item)
          return [
          'result' => false,
          'status' => 0,
          'message' => '未有下單貨品',
          ];
          else if(count($itemIds) == 0) // If all the items are deleted
              Shippingitem::where('shippingId', $shipment['shippingId'])->delete();
          else
              Shippingitem::whereNotIn('id', $itemIds)->where('shippingId', $shipment['shippingId'])->delete();

          } 
          
       
        foreach($shipItem as $k)
        {
           
            $this->sh->setItems($k['dbid'],$k['containerId'],$k['serial_no'],$k['container_size'],$k['container_receiveDate'],$k['container_Num'],$k['container_weight'],$k['container_capacity'],$k['remark'],$k['deleted']);
        }

        
         $message = $this->sh->save();
        
        return Response::json($message);
    }
    
    public function jsonQueryShip() 
    {
        $mode = Input::get('mode');
        $filter = Input ::get('filterData');
        $current_sorting = $filter['current_sorting'];
        $sorting = "shippingId";
        if(!$filter['sorting'] == '')
        {
            $sorting = $filter['sorting'];
        }
        
        if ($mode == 'collection') {
            
            $ship = Shipping::select(['shippingId','shippings.supplierCode','suppliers.supplierName','etaDate','shippings.status','carrier','bl_number','users.username','shippings.updated_at','shippings.poCode'])
                    ->leftJoin('suppliers', function($join) {
                        $join->on('suppliers.supplierCode', '=', 'shippings.supplierCode');
                    })
                    ->leftJoin('users', function($join) {
                        $join->on('users.id', '=', 'shippings.updated_by');
                    })
                    ->orderby($sorting,$current_sorting);
                    

             $ship->where('shippings.shippingId','LIKE','%'. $filter['shippingId'] .'%')
                   ->where('shippings.status','LIKE','%'. $filter['status'] .'%')
                   ->where('shippings.supplierCode','LIKE','%'. $filter['supplier'] .'%');
                           
                     
                     
                    
                    //Dont add get() here
              return Datatables::of($ship)
                            ->addColumn('link', function ($shi) {
                                return '<span onclick="editShip(\'' . $shi->shippingId . '\')" class="btn btn-xs default"><i class="fa fa-search"></i> 檢視</span>';
                            })
                             ->editColumn('status', function($shi) {
                                $statusValue = "";
                                if ($shi->status == 1) {
                                    $statusValue = "正常";
                                } else if ($shi->status == 30) {
                                    $statusValue = "已完成";
                                } else if ($shi->status == 99) {
                                    $statusValue = "暫停";
                                }
                                return $statusValue;
                            })
                            ->make(true);
        }
        
       return Response::json($ship);
        
    }
    
    public function jsonGetSingleShip()
    {
        $shippingId = Input::get('shippingId');

        $base = Shipping::where('shippingId', $shippingId);

        $shipping = Shipping::getFullShippment($base);
		

        $returnInformation = [
            'shipping' => array_values($shipping['shipping'])[0],
            'shippingItem' => array_values($shipping['shipping'])[0]['shippingitem'],
        ];
        return Response::json($returnInformation);
    }
	
	public function deleteShip()
	{
		 $shippingId = Input::get('shippingId');
		 
		 $shipment =  new shippingMan($shippingId); 
		 return Response::json($shipment->setDeleteShip());
		 
	}
}