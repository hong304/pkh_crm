<?php

class ProductSearchCustomerMap extends Eloquent  {

    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'productsearch_customer_map';
	
	public function productDetail()
	{
	     
	    return $this->hasOne('Product', 'productId', 'productId');
	}
	
}