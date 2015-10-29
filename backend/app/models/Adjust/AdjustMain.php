<?php

class AdjustMain
{
    private $id = '';
    private $action = '';
    public function  __construct()
    {
       // $this->action = $id ? "update": "create";
       // if($this->action == "create")
      //  {
            $this->ad = new adjust();
      //  }
    /*    }else if($this->action == 'update')
        {
             $this->ad = adjust ::where('id',$id)->firstOrFail();
        }*/
    }
             
    
    public function generateId($num)
    {
        $newReceiving = "n";
        $storeReceiveId = array();
        $ori = 100;
        for($c = 0;$c < $num;$c++)
        {
            $ori++;
            $storeReceiveId[$c] = $newReceiving.$ori;
        }
        return $storeReceiveId; 
    }
    
    public function setItems($adjustId,$adjustType,$good_qty,$productId)
    {
        $this->items[] = [
            'adjustId' => $adjustId,
            'adjustType' => $adjustType,
            'adjust_qty' => $good_qty,
            'productId' => $productId,
            'receivingId'=>''
        ];
        return $this->items;
    }
    
    public function setReceiveItems($adjustId,$good_qty,$productId,$unit)
    {
        $this->receiveitems[] = [
            'adjustId' => $adjustId,
            'adjust_qty' => $good_qty,
            'productId' => $productId,
            'unit'=>$unit,
            'receivingId'=>''
        ];
        return $this->receiveitems;
    }
    
    public function prepareItems()
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
            $u = 0;
            foreach($this->items as $k=>$v)
            {
                if($v['receivingId'] == "")
                {
                    $this->items[$u]['receivingId'] = $this->receivingId[$u];
                    $this->receiveitems[$u]['receivingId'] = $this->receivingId[$u];
                }
                if($v['productId'] == "")
                {
                    unset($this->items[$k]);
                    unset($this->receiveitems[$k]);
                }
                $u++;
            }
        }
    }
    
    public function save()
    {
        $this->receivingId = $this->generateId(count($this->items));
        $this->prepareItems();
        if(isset($this->items))
        {
            foreach($this->items as $item)
            {
                $items = new adjust();
                 foreach($item as $k=>$v)
                 {
                     $items->$k = $v;
                 }
                $items->updated_at = $items->created_at = time();
                $items->updated_by = $items->created_by = Auth::user()->id; 
                $items->save();
            }
            return[
               'result' => true,
               'action' => 'create',
               'status'=> $this->receiveitems
            ];
        }else
        {
            return[
               'result' => false,
                'action'=> 'fail',
                'status'=> '沒有包裝貨品'
            ];
        }
    }

}