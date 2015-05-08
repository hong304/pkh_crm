<?php

class Customer extends Eloquent  {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'Customer';
	
	protected $primaryKey = 'customerId';

    public static function boot()
    {
        parent::boot();

        Customer::saving(function ($model) {

            unset($model->zoneText);
            unset($model->paymentTermText);
            unset($model->created_atText);

        });
    }

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

    public function data_invoice(){
        return $this->hasMany('data_invoice','customer_id','customerId');
    }

    public function newCollection(array $models = array())
    {

        foreach($models as $model)
        {

           if($model->paymentTermId == 1)
                $model->paymentTermText = 'COD';
            else if ($model->paymentTermId == 2)
                $model->paymentTermText = 'Credit';

            $model->zoneText = Config::get('zoneName.'.$model->deliveryZone);

            $model->created_atText = date("Y-m-d H:i:s", $model->created_at);

        }


        return new Collection($models);
    }

}