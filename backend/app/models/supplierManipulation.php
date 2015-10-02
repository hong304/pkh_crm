<?php


class supplierManipulation {

    private $_supplierCode = '';
    public function __construct($supplierCode = false,$i)
    {
        $this->action = $supplierCode ? 'update' : 'create';
    
        if($this->action == 'create')
        {
            $this->_supplierCode = $supplierCode;
            $this->generateId($i['countryId'],$i['supplierAbbre']);

            $this->im = new Supplier();
            $this->im->created_by = Auth::user()->id;
             
        }
        elseif($this->action == 'update')
        {
            $this->_supplierCode = $supplierCode;
            if($this->_supplierCode !=  $i['supplierCodeOri'])
            {
                $this->generateId($i['countryId'],$i['supplierAbbre']);
                $supplier = Supplier::where('supplierCode', $this->_supplierCode)->first();
                $supplierNum = count($supplier);    
                if($supplierNum > 0) // If the generated Id is duplicated , then try to generate again, If cannot probably do while loop until the Id is not duplicated 
                {
                    $this->generateId($i['countryId'],$i['supplierAbbre']);
                }
               // ($supplierNum > 0) ? $this->generateId($i['countryId']) : ""; If the code is not the same , then generate the Id again,so no need to do comparision
            }
            $this->im = Supplier::where('supplierCode', $i['supplierCodeOri'])->firstOrFail(); // must pass existed code here , cannot pass new supplierCode, since the error will occur 
        
            $this->im->updated_by = Auth::user()->id;
        }
        
    }
    
        
     public function generateId($countryId,$supplierAbbre)
	{
	    $length = 4;
	    $prefix = '4';
            $supplierNum  = "";
            $storeNext = "";
         
	    $lastSupplier = Supplier::where('supplierCode', 'like',  $supplierAbbre.$countryId.'%')->Orderby('supplierCode','desc')->first();
            if(count($lastSupplier) > 0 )
            {
                 $supplierNum =  $lastSupplier->supplierCode;
            }
            $regex = "/[a-zA-Z]/";
            $nextStore = (int)(substr($supplierNum,4)) + 1;
            $nextId = str_pad($nextStore, $length, '0', STR_PAD_LEFT);
            $storeNext = $supplierAbbre . $countryId . $nextId;

	    
	    $this->_supplierCode = $storeNext;
	    return  $countryId;	    
	}
	
	public function save($info)
	{
	    $fields = ['address', 'address1','address2','contactPerson_1', 'contactPerson_2', 'email', 'fax_1', 'fax_2', 'phone_1', 'phone_2', 'remark'];
	    
            $fieldsManager = ['countryId','status','payment','creditDay','creditLimit','creditAmount','currencyId','supplierName','location'];
	    foreach($fields as $f)
	    {
	        $this->im->$f = $info[$f];
	    }

             if(Auth::user()->can('allow_edit'))
             {
                 foreach($fieldsManager as $a)
                 {
                     $this->im->$a = $info[$a];

                 }
                 $this->im->supplierCode = strtoupper($this->_supplierCode); // force the code to be uppercase
             }
             
            
            $this->im->updated_by = Auth::user()->id;
	    $this->im->save(); // $this->im
	    
	    return $this->_supplierCode;
	    
	}
}