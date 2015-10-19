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
         $newContainerCode = "r";
         $suffix = 10000;
         $re = Receiving :: select('*')->orderby('receivingId','desc')->first();
         if(count($re) == 0)
         {
             $this->newContainerCode = $newContainerCode . $suffix;
         }else
         {
             $b = str_replace("r", "", $re->toArray()['receivingId']);
             $newCode = (int)$b +1;
             $this->newContainerCode = $newContainerCode . $newCode;
         }
 
         return $this->newContainerCode;
     }
     
     public function setItemss($dbid,$poCode,$shippingId,$containerId,$receivingId,$productId,$good_qty,$damage_qty,$on_hold_qty,$expiry_date,$rec_good_qty,$rec_damage_qty,$receiving_date,$unit_cost,$bin_location,$deleted)
     {
         $this->items[] = [
             'id' => $dbid,
             'poCode' => $poCode,
             'shippingId' => $shippingId,
             'containerId' => $containerId,
             'receivingId' => $receivingId,
             'productId' => $productId,
             'good_qty' => $good_qty,
             'damage_qty' => $damage_qty,
             'on_hold_qty' => $on_hold_qty,
             'expiry_date' => $expiry_date,
             'rec_good_qty' => $rec_good_qty,
             'rec_damage_qty' => $rec_damage_qty,
             'receiving_date' => $receiving_date,
             'unit_cost' => $unit_cost,
             'bin_location' => $bin_location,
             'deleted' => $deleted,
         ];
         
         return $this->items;
     }
     
     //Use to filter the unwanted message
     public function prepare_items()
     {
         
         if(!isset($this->items))
         {
               return [
                    'result' => false,
                   'status'=>0,
                    'message' => '無貨物輸入',
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
                           if($k !=='id' && $k !== 'deleted' && $k !== 'receivingId') 
                               $item->$k = $v;
                       }

                    $item->save();
               }
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
                    'message' => '無貨物輸入',
                ];
         }
      
     }
     
     
  
}