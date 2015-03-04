<?php


class ProductManipulation {

    private $_productId = '';
    
    public function __construct($productId = false, $group)
    {
        $this->action = $productId ? 'update' : 'create';
                
        if($this->action == 'create')
        {
            $this->generateId($group);
            $this->im = new Product();
            
        }
        elseif($this->action == 'update')
        {
            $this->im = Product::where('productId', $productId)->firstOrFail();
            
            
            $this->_productId = $productId;
        }
    }
    
    public function generateId($group)
	{
	    $length = 3;
	    
	    $prefix = $group;
	    $lastid = Product::where('productId', 'like', $prefix.'%')->limit(1)->orderBy('productId', 'Desc')->first();
	    	    
	    if(count($lastid) > 0)
	    {
	        // extract latter part
	        $i = explode('-', $lastid->productId);
	        $nextId = (int) $i[2] + 1;
	        $nextId = $prefix . str_pad($nextId, $length, '0', STR_PAD_LEFT);
	    }
	    else
	    {
	        $nextId = $prefix.str_pad('1', $length, '0', STR_PAD_LEFT);
	    }
	    
	    $this->_productId = $nextId;
	    
	    return $this;	    
	}
	
	public function save($info)
	{
        
	    $fields = ['productId','productPacking_carton', 'productCost_unit', 'productPacking_inner', 'productPacking_unit','productPacking_size','productPackingName_carton','productPackingName_inner','productPackingName_unit','productPackingInterval_carton','productPackingInterval_inner','productPackingInterval_unit','productStdPrice_carton','productStdPrice_inner','productStdPrice_unit','productMinPrice_carton','productMinPrice_inner','productMinPrice_unit','productName_chi','productName_eng',];
	    
	    foreach($fields as $f)
	    {
	        
	        $this->im->$f = $info[$f];
	    }
        $this->im->productLocation = $info['productLocation']['value'];
        $this->im->productStatus = $info['productStatus']['value'];
        //dd($this->im);
        unset($this->im->productPacking);
        unset($this->im->productPackingName);
        unset($this->im->productStdPrice);
        unset($this->im->productPackingInterval);
        unset($this->im->productMinPrice);
	    $this->im->productId = $this->_productId;
	    $this->im->save();
	    
	    return $this->_productId;
	    
	}
}