<?php


class ProductManipulation {

    private $_productId = '';
    private $_departmentid = '';
    private $_groupid = '';
    
    public function __construct($productId = false, $group, $productNewId)
    {
        $this->action = $productId ? 'update' : 'create';
                
        if($this->action == 'create')
        {
           // $this->generateId($group);
            $groupid =  substr($group, 0, -1);
            $pieces = explode("-",$groupid);
            $this->_departmentid = $pieces[0];
            $this->_groupid = $pieces[1];
            $this->_productId = $productNewId;
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



        $length = 6;
	    
	//    $prefix = $group;

        $groupid =  substr($group, 0, -1);
        $pieces = explode("-",$groupid);
        $this->_departmentid = $pieces[0];
        $this->_groupid = $pieces[1];

	    $lastid = Product::where('department', $pieces[0])->where('group', $pieces[1])->where('new_product_id',0)->orderBy('productId', 'Desc')->first();

        $prefix = '';

	    if(count($lastid) > 0) {
            // extract latter part
            //  $i = explode('-', $lastid->productId);
            //  $nextId = (int) $i[2] + 1;
            //   $nextId = $prefix . str_pad($nextId, $length, '0', STR_PAD_LEFT);

//echo $lastid->productId;

            if (strlen((string)$lastid->productId) == 6){
              //  p('1');
                $nextId = (int)$lastid->productId + 1;
               // pd($nextId);
                $nextId = str_pad($nextId, $length, '0', STR_PAD_LEFT);
            }else {
               // p('2');
                //$nextId = (int) substr($lastid->productId, 1) + 1;
                // $nextId = substr($lastid->productId,0, 1).str_pad($nextId, $length, '0', STR_PAD_LEFT);
                $alpha = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X ', 'Y', 'Z');
                $inGroup = strtoupper($pieces[0]);
                foreach ($alpha as $k => $v) {
                    if ($v == $inGroup)
                        $prefix = $k + 1;
                }
                if($prefix == '') $prefix = 27;
                    $nextId = $prefix . $pieces[1] . '01';
                $nextId = str_pad($nextId, $length, '0', STR_PAD_LEFT);
            }


        }else
	    {
          //  p('3');
	       // $nextId = str_pad('1', $length, '0', STR_PAD_LEFT);
            $alpha = array('A','B','C','D','E','F','G','H','I','J','K', 'L','M','N','O','P','Q','R','S','T','U','V','W','X ','Y','Z');
            $inGroup =  strtoupper($pieces[0]);
            foreach ($alpha as $k => $v){
                if($v == $inGroup)
                    $prefix = $k+1;
            }
            if($prefix == '') $prefix = 27;
                $nextId = $prefix.$pieces[1].'01';
            $nextId = str_pad($nextId, $length, '0', STR_PAD_LEFT);
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