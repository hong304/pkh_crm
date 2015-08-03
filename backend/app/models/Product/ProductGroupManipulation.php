<?php

class ProductGroupManipulation
{
    public $action = "";
    public $_productGroupId = "";
    public $_productDepartmentName= "";
    public $passObject = "";
    public function __construct($departmentId,$groupId,$gpObject)
    {
        $this->action = $groupId ? 'update' : 'create';
        $this->passObject = $gpObject;
        if($this->action == "create")
        {
            $this->pg = new ProductGroup();
            $this->pg->created_by = Auth::user()->id;
            $existOrNot = count(ProductGroup :: where("productDepartmentId",$departmentId)->get());
            $this->generateId($existOrNot,$departmentId);
        }
        else if($this->action == "update")
        {
            $this->pg = ProductGroup ::where('productGroupId', $groupId)->where('productDepartmentId', $departmentId)->firstOrFail();
            $this->_productGroupId = $groupId;
            $this->_productDepartmentName = $this->passObject['productDepartmentName']; 
        }
    }
    
    public function generateId($num,$departmentId)
    {
        if($num <= 0)
        {
            $productgroup = ProductGroup :: Select("productGroupId")->orderby("productGroupId","desc")->first()->toArray();
            $this->_productGroupId = "01";
            $this->_productDepartmentName = $this->passObject['productDepartmentName'];
        }
        else
        {
            $productgroup = ProductGroup :: Select("productGroupId","productDepartmentName")->where("productDepartmentId",$departmentId)->orderby("productGroupId","desc")->first()->toArray();
            $storeProductGroupId = (int)$productgroup['productGroupId'] + 1;
            $this->_productDepartmentName = $productgroup['productDepartmentName'];
            $this->_productGroupId = str_pad($storeProductGroupId,2,"0" ,STR_PAD_LEFT);
        }
        return $this->_productGroupId;
    }
    

    public function save($info)
    {
	    $fields = ['productGroupName','productDepartmentId']; // put elements that are passed by info only,not via other way eg , return by sql
	    
            foreach($fields as $f)
            {
                $this->pg->$f = $info[$f];
            }
            $this->pg->productGroupId = $this->_productGroupId;
            $this->pg->productDepartmentName = $this->_productDepartmentName;
            $this->pg->updated_by = Auth::user()->id;
	    $this->pg->save();
	    
	    return $this->pg->productGroupId;
    }
}