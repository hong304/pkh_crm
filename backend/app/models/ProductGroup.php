<?php

class ProductGroup extends Eloquent  {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'productgroup';

	
	public static function getInheritatedGroupList()
	{
	    $groups = ProductGroup::all();
	    
	    foreach($groups as $g)
	    {
	        $rowidentifier = $g['productDepartmentId'].md5($g['productDepartmentId'].$g['productDepartmentName']);
	        $inhert[$rowidentifier][] = [
	           'rowidentifier' => $rowidentifier,
	           'productDepartmentId' => $g['productDepartmentId'],
	           'productDepartmentName' => $g['productDepartmentName'],
	           'productGroupId' => $g['productGroupId'],
	           'productGroupName' => $g['productGroupName'],
	        ];
	    }
	    //dd($inhert);
	    return $inhert;
	}
	
}