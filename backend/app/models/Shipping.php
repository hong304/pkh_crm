<?php

class Shipping extends Eloquent  {

    public static function getFullShippment($ele)
    {
        $shippings = $ele;
  
        $shippings = $shippings->with('Shippingitem','Supplier')->get();
		
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
    
    public function Shippingitem()
    {
	    return $this->hasMany('Shippingitem', 'shippingId', 'shippingId');
    }
	
	public function Supplier()
	{
		 return $this->hasMany('Supplier', 'supplierCode', 'supplierCode');
	}
}
