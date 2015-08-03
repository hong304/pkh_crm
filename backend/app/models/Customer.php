<?php
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Carbon\Carbon;
class Customer extends Eloquent  {
    protected $primaryKey = 'customerId';
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    use SoftDeletingTrait;

    protected $dates = ['deleted_at'];
	protected $table = 'customer';
	


    public static function boot()
    {
        parent::boot();

        Customer::saving(function ($model) {

            unset($model->zoneText);
            unset($model->paymentTermText);
            unset($model->created_atText);
            unset($model->updated_by_text);

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

    public function group()
    {
        return $this->hasOne('customerGroup','id','customer_group_id');
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
            $model->updated_by_text = Config::get('userName.'.$model->updated_by);

            //$model->created_atText = date("Y-m-d H:i:s", $model->created_at);

        }


        return new Collection($models);
    }

 /*   public function getUpdatedAtAttribute($attr) {
     //   return Carbon::parse($attr)->format('d/m/Y - h:ia'); //Change the format to whichever you desire
        return date("Y-m-d H:i:s", $attr);
    }*/

}