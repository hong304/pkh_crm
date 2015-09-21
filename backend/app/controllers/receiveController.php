<?php

class receiveController extends BaseController {
    
    public function searchSupplier()
    {
        $id = Input::get('id');
        $location = 0;
        if(Input ::get('location') != '')
        {
            $location = Input ::get('location');
        }
        $supplier = Supplier::select('supplierCode','supplierName','countries.countryName','countries.countryId','phone_1','status','currencies.currencyId','currencies.currencyName')->where('supplierCode',$id)->where('location',$location)->where('status',1)
                     ->leftJoin('countries', function($join) {
                        $join->on('suppliers.countryId', '=', 'countries.countryId');
                      })
                       ->leftJoin('currencies', function($joins) {
                        $joins->on('suppliers.currencyId', '=', 'currencies.currencyId');
                      })
                ->get();
       
        return Response::json($supplier); 
    }
    
    
    public function searchPo()
    {
        $id = Input::get('poCode');
        $location = 1;
        if(Input ::get('location') != '')
        {
            $location = Input ::get('location');
        }
         $po = Purchaseorder::select('poCode','suppliers.supplierName','poDate','etaDate','phone_1','suppliers.status')->where('suppliers.supplierCode',$id)->where('suppliers.location',$location)->where('poStatus','!=','99')
                 ->leftJoin('suppliers', function($join) {
                        $join->on('suppliers.supplierCode', '=', 'purchaseorders.supplierCode');
                      })
                 ->get();
         return Response::json($po); 
    }
    
    public function searchShipping()
    {
        $id = Input::get('poCode');
        $shipping = Shipping :: select('shippingId')->with('Shippingitem')->where('poCode',$id)->where('status','!=','99')->get();
        $formatShipping = $shipping->toArray();
        $countCount = 0;
        for($g = 0;$g <count($formatShipping);$g++)
        {
            for($start = 0;$start<count($formatShipping[$g]['shippingitem']);$start++)
            {
               
                $storeShip[$countCount]['shippingId'] = $formatShipping[$g]['shippingId'];
                $storeShip[$countCount]['containerId'] = $formatShipping[$g]['shippingitem'][$start]['containerId'];
                $storeShip[$countCount]['dbid'] = $formatShipping[$g]['shippingitem'][$start]['id'];
                $countCount++;
            }
        }
        return Response::json($storeShip); 
    }
    
    public function getPurchaseAll()
    {
        $poCode = Input::get('poCode');
        $poThings = Purchaseorder :: where('poCode',$poCode)->with(['Poitem'=>function($query){$query->with('productDetail');}])->get();
        return Response::json($poThings); 
    }
    
    
    public function newReceive()
    {
        $location = Input :: get('location');
        if(isset($location))
        {
            $object = Input::get('order');
            $product = Input::get('product');
            $receiveId = isset($object['receivingId']) ? $object['receivingId'] : '';
    
                if($location == 2)
                {
                    $input = Input :: get('shippingdbid');
                    $shippingId = Input::get('order')['shippingId'];

                    //Insert the details into shippingitems
                    $this->re = new shippingMan($shippingId);

                    if(isset($input))
                    {
                        $this->re->setOtherItems($input, $object['receiveDate'], $object['currencyId'], $object['total_cost'], $object['feight_local_cost'], $object['hk_local_cost'], $object['local_cost']);
                        $this->re->saveOtherItems($input);
                    }
                    //return Response::json($this->sh); 
                }
                
                $have_item = false;    //fOR UPDATE
                $itemIds = [];
                foreach ($product as $p) {
                    if ($p['dbid'] != '' && $p['productId'] != '' && $p['deleted'] == 0)
                        $itemIds[] = $p['dbid'];   //valid originally existed
                    if ($p['dbid'] == '' && $p['productId'] != '' && $p['deleted'] == 0)
                        $have_item = true;   //valid newly created
                }

        //Below should be uncomment when the update function is ready
        if ($receiveId !== '') {  //update
            if (count($itemIds) == 0 && !$have_item)
                return [
                    'result' => false,
                    'status' => 0,
                    'message' => '未有貨品',
                ];
            else if (count($itemIds) == 0) // If all the items are deleted
                Receiving::where('receiveId', $receiveId)->delete();
            else
                Receiving::whereNotIn('id', $itemIds)->where('receiveId', $receiveId)->delete();
            //If there is no shippingId, not only the records deleted in ui but also records in db will be deleted.
        }

                    //Insert records into receiving tables
                $this->new = new ReceiveMan($receiveId);
                foreach($product as $k=>$v)
                {
                    if($v['deleted'] == 0 && isset($v['productId']) && $v['productId'] !== "" )
                         $this->new->setItemss($v['dbid'],$object['poCode'],$object['shippingId'],$object['containerId'],$object['receivingId'],$v['productId'],$v['good_qty'],$v['damage_qty'],$v['on_hold_qty'],$v['expiryDate'],$v['good_qty'],$v['damage_qty'],$object['receiveDate'],$v['unit_cost'],$v['bin_location'],$v['deleted']);
                }
                $message = $this->new->save();
                
                return Response::json($message);
 
        }else
        {
              return [
                    'result' => false,
                    'status' => 0,
                    'message' => '請重新選擇',
                ];
        }

    }
    
   
}