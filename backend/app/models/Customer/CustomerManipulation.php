<?php


class CustomerManipulation {

    private $_customerId = '';
    public function __construct($customerId = false,$customerNewId)
    {
        $this->action = $customerId ? 'update' : 'create';
                
        if($this->action == 'create')
        {
             if($customerNewId)
                $this->_customerId = $customerNewId;
            else
                $this->generateId();

            $this->im = new Customer();
            $this->im->created_by = Auth::user()->id;
            
        }
        elseif($this->action == 'update')
        {
            $this->im = Customer::where('customerId', $customerId)->firstOrFail();
            $this->im->updated_by = Auth::user()->id;
            
            $this->_customerId = $customerId;
        }
    }
    
    public function generateId()
	{
	    $length = 5;


        $prefix = '3';
        $lastcustomer = Customer::where('customerId', 'like', $prefix.'%')->limit(1)->orderBy('customerId', 'Desc')->first();

	    if(count($lastcustomer) > 0)
	    {
	        $nextId = (int) $lastcustomer->customerId + 1;
	        //$nextId = str_pad($nextId, $length, '0', STR_PAD_LEFT);
	    }
	    else
	    {
	        $nextId = $prefix.str_pad('1', $length, '0', STR_PAD_LEFT);
	    }

	    $this->_customerId = $nextId;
	    
	    return $this;	    
	}
	
	public function save($info)
	{

	    $fields = ['address_chi', 'address_eng', 'contactPerson_1', 'contactPerson_2', 'currencyId', 'customerId', 'customerName_chi', 'customerName_eng', 'customerTypeId', 'discount', 'email', 'fax_1', 'fax_2', 'phone_1', 'phone_2', 'paymentTermId', 'routePlanningPriority', 'remark','customer_group_id','account_contact','account_tel','account_fax','unlock','discount'];
	    
	    foreach($fields as $f)
	    {
	        $this->im->$f = $info[$f];
	    }
	    $this->im->deliveryZone = $info['deliveryZone']['zoneId'];
	    $this->im->customerId = $this->_customerId;
	    $this->im->status = $info['status']['value'];
        $this->im->shift = $info['shift']['value'];
        $this->im->updated_by = Auth::user()->id;
	    $this->im->save();
	    
	    return $this->_customerId;
	    
	}
}