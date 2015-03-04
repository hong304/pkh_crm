<?php

class Customer extends Eloquent  {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'Customer';
	
	protected $primaryKey = 'customerId';
	
	public function scopeCustomerInMyZone()
	{
	    // get my zone info
	    $myzones = UserZone::getMyZone();
	    
	    $customers = Customer::wherein('deliveryZone', $myzones);
	    
	    return $customers;
	}
	
	public function zone()
	{
	    return $this->hasOne('Zone', 'zoneId', 'deliveryZone');
	}
}