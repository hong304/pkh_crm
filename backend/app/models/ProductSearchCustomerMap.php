<?php

class ProductSearchCustomerMap extends Eloquent  {

    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'ProductSearch_Customer_Map';
	
	public function productDetail()
	{
	     
	    return $this->hasOne('Product', 'productId', 'productId');
	}
	
}