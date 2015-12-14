<?php

class ReceiveRepackMain
{
    private $receiveId;
    
    public function  __construct()
    {
    }
    
     public function setAdjustItems($receivingId,$productId,$good_qty,$expiry_date,$supplier_unitName,$adjust_id)
     {
         $this->items[] = [
             'receivingId' => $receivingId,
             'productId' => $productId,
             'good_qty' => $good_qty,
             'expiry_date' => $expiry_date,
             'rec_good_qty' => $good_qty,
             'supplier_interval'=>'unit',
             'supplier_unitName'=>$supplier_unitName,
             'rec_receiveQty'=>$good_qty,
             'adjustId'=>$adjust_id
         ];
         return $this->items;
     }
     
     public function prepareItems()
    {
        if(!isset($this->items))
        {
             return [
                    'result' => false,
                   'status'=>0,
                    'message' => '無貨物輸入3',
              ];
        }else
        {
            $u = 0;
            foreach($this->items as $k=>$v)
            {
                if($v['receivingId'] == "")
                {
                    $this->items[$u]['receivingId'] = $this->receivingId[$u];
                }
                if($v['productId'] == "")
                {
                    unset($this->items[$k]);
                }
                $u++;
            }
        }
    }
    
     public function save()
     {
          $this->prepareItems();
          if(isset($this->items)){
             foreach($this->items as $i)
             {
                //create
                $item = new Receiving();
                $item->updated_at = $item->created_at = time();
                $item->updated_by = $item->created_by = Auth::user()->id; 
                
                foreach($i as $k=>$v)
                {
                    $item->$k = $v;
                }
                    $item->save();
              }
                return[
                    'result' => true,
                    'action' => 'create',
                    'status' => $this->items
                ];
         }else
         {
              return [
                    'result' => false,
                     'status' => 0,
                    'message' => '無貨物輸入4',
                ];
         }
     }
     
    
}
