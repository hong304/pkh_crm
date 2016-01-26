<?php

class ReceiveMan 
{
     public $newContainerCode = "";
     public $action = "";
     
     public function __construct($receiveId = false)
     {
          $this->action = $receiveId ? "update": "create";
          if($this->action == "create")
          {
              $this->newContainerCode = $this->generateId(); 
              $this->re = new Receiving();
          }else
          {
              $this->newContainerCode = $receiveId;
              $this->re = Receiving ::where('receivingId',$receiveId)->firstOrFail();
          }
     }
     
     public function generateId()
     {
         $newContainerCode = "R";
         $suffix = 10000;
         $re = Receiving :: select('*')->where('receivingId', 'LIKE',  'R' . '%')->orderby('receivingId','desc')->first();
         if(count($re) == 0)
         {
             $this->newContainerCode = $newContainerCode . $suffix;
         }else
         {
             $b = str_replace("R", "", $re->toArray()['receivingId']);
             $newCode = (int)$b +1;
             $this->newContainerCode = $newContainerCode . $newCode;
         }
 
         return $this->newContainerCode;
     }
     
     public function setItemss($dbid,$poCode,$shippingId,$containerId,$receivingId,$productId,$good_qty,$damage_qty,$on_hold_qty,$expiry_date,$rec_good_qty,$rec_damage_qty,$receiving_date,$unit_cost,$bin_location,$deleted,$unitlevel,$rec_qty,$unitName)
     {
        
         $productDetails = Product :: select('productPacking_unit','productPacking_inner','productPacking_carton')->where('productId',$productId)->first()->toArray();
         $mutiply = $this->reunit($unitlevel,$productDetails['productPacking_unit'],$productDetails['productPacking_inner']);
         $unitCost = $this->unitProductCost($unit_cost,$unitlevel,$productDetails['productPacking_unit'],$productDetails['productPacking_inner']);
         $cartonCost = $this->cartonProductCost($unit_cost,$unitlevel,$productDetails['productPacking_unit'],$productDetails['productPacking_inner']);

         $this->items[] = [
             'id' => $dbid,
             'poCode' => $poCode,
             'shippingId' => $shippingId,
             'containerId' => $containerId,
             'receivingId' => $receivingId,
             'productId' => $productId,
             'good_qty' => $good_qty * $mutiply,
             'damage_qty' => $damage_qty * $mutiply,
             'on_hold_qty' => $on_hold_qty * $mutiply,
             'expiry_date' => $expiry_date,
             'rec_good_qty' => $rec_good_qty * $mutiply,
             'rec_damage_qty' => $rec_damage_qty * $mutiply,
             'receiving_date' => $receiving_date,
             'unit_cost' => $unitCost,
             'carton_cost' => $cartonCost,
             'bin_location' => $bin_location,
             'deleted' => $deleted,
             'rec_receiveQty'=> $rec_qty,
             'supplier_interval'=>$unitlevel,
             'supplier_unitName'=>$unitName,
             'receivedQty' => $good_qty+$damage_qty+$on_hold_qty
         ];

         return $this->items;
     }
     
     //Use to filter the unwanted message
     public function prepare_items()
     {
       //  pd($this->items);

         if(!isset($this->items))
         {
               return [
                    'result' => false,
                   'status'=>0,
                    'message' => '無貨物輸入1',
                ];
         }else
         {
               foreach($this->items as $k=>$v)
               {
                    if($v['deleted'] == 1 || !isset($v['productId']) || !isset($v['deleted']))
                    {
                        unset($this->items[$k]);
                    }
                }
         
         }
     }
     
    public function reunit($unitlevel,$productPacking_unit,$productPacking_inner)
    {
       $multiply = 1;
       if($unitlevel == 'carton')
       {
           $multiply *= $multiply * $productPacking_inner * $productPacking_unit;
       }else if($unitlevel == 'inner')
       {
           $multiply *= $multiply * $productPacking_unit;
       }else if($unitlevel == 'unit')
       {
           $multiply *= $multiply;
       }
        return $multiply;
    }
    
    public function unitProductCost($unitCost,$unitlevel,$unit,$inner)
    {
        $miniCost = 0;
        if($unitlevel == 'carton')
            $miniCost = $unitCost / $inner / $unit;
        else if($unitlevel == 'inner')
            $miniCost = $unitCost / $inner;
        else if($unitlevel == 'unit')
            $miniCost = $unitCost;
        return $miniCost;
    }

    public function cartonProductCost($unitCost,$unitlevel,$unit,$inner)
    {
        $miniCost = 0;
        if($unitlevel == 'carton')
            $miniCost = $unitCost;
        else if($unitlevel == 'inner')
            $miniCost = $unitCost * $inner;
        else if($unitlevel == 'unit')
            $miniCost = $unitCost *  $inner * $unit;
        return $miniCost;
    }

     public function save()
     {

         $this->prepare_items();
         if(isset($this->items))
         {
             foreach($this->items as $i)
             {
                      if($i['id'] !== "")
                      {
                      //update
                          $item = Receiving::where('id', $i['id'])->first();
                          $item->updated_at = time();
                          $item->updated_by = Auth::user()->id;
                          $item->receivingId = $this->newContainerCode;
                          
                      }else
                      {
                      //create
                          $item = new Receiving();
                          $item->updated_at = $item->created_at = time();
                          $item->updated_by = $item->created_by = Auth::user()->id; 
                          $item->receivingId = $this->newContainerCode;
                       }
                      
                       foreach($i as $k=>$v)
                       {
                           if($k !=='id' && $k !== 'deleted' && $k !== 'receivingId' && $k !== 'receivedQty' && $k !== 'carton_cost')
                               $item->$k = $v;
                       }

                 $poitems = poItem::where('poCode',$i['poCode'])->where('productId',$i['productId'])->where('productQtyUnit',$i['supplier_interval'])->first();
                 $poitems->receivedQty += $i['receivedQty'];
                 $poitems->save();

                 $item->save();

                 $products = Product::where('productId',$i['productId'])->first();
                 $products->productCost_unit = $i['carton_cost'];
                 $products->save();
               }

                $po = Purchaseorder::where('poCode',$this->items[0]['poCode'])->first();
                $po->poStatus = 20;
                $po->save();

                return[
                    'result' => true,
                    'action' => 'create',
                    'receiveid' => $this->newContainerCode,
                ];
         }else
         {
              return [
                    'result' => false,
                     'status' => 0,
                    'message' => '無貨物輸入2',
                ];
         }
      
     }
     
     
  
}
