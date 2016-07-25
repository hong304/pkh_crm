<?php

class Shipping extends Eloquent  {

    public function containers() //containers
    {
        return $this->hasMany('Container', 'shippingId', 'shippingId');
    }

    public function Supplier()
    {
        return $this->hasOne('Supplier', 'supplierCode', 'supplierCode');
    }

    public function purchaseOrder()
    {
        return $this->hasOne('Purchaseorder', 'poCode', 'poCode');
    }

    public static function getFullShippment($ele)
    {
        $shippings = $ele;
  
        $shippings = $shippings->with(['containers' => function ($query) {
            $query->with('containerproduct');
        }])->with('Supplier')->get();
		
		$total = $shippings->count();
		 
		if($total > 0)
		{
			 $returnInfo = [
				'count' => $total,
				'shipping' => $shippings->toArray(),
	       ];
		}
		
        return $returnInfo;
      

    }
    

        
  
}
