<?php


class ProductManipulation {

    private $_productId = '';
    private $_departmentid = '';
    private $_groupid = '';
    
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
	    
	//    $prefix = $group;

        $groupid =  substr($group, 0, -1);
        $pieces = explode("-",$groupid);
        $this->_departmentid = $pieces[0];
        $this->_groupid = $pieces[1];

	    $lastid = Product::where('department', $pieces[0])->where('group', $pieces[1])->limit(1)->orderBy('productId', 'Desc')->first();
	    	    
	    if(count($lastid) > 0)
	    {
	        // extract latter part
	      //  $i = explode('-', $lastid->productId);
	      //  $nextId = (int) $i[2] + 1;
	     //   $nextId = $prefix . str_pad($nextId, $length, '0', STR_PAD_LEFT);

            if(is_numeric($lastid->productId)){
                $nextId = (int) $lastid->productId + 1;
            }else{
                $nextId = (int) substr($lastid->productId, 1) + 1;
                $nextId = substr($lastid->productId,0, 1).str_pad($nextId, $length, '0', STR_PAD_LEFT);
            }

	    }
	    else
	    {
	        $nextId = str_pad('1', $length, '0', STR_PAD_LEFT);
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

        if($this->action == 'create'){
            $this->im->department = $this->_departmentid;
            $this->im->group = $this->_groupid;
        }

	    $this->im->save();
	    
	    return $this->_productId;
	    
	}
}