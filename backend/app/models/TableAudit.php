<?php

//use Illuminate\Database\Eloquent\SoftDeletingTrait;

class TableAudit extends Eloquent  {

    //use SoftDeletingTrait;
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    //protected $with = ['User'];
	protected $table = 'TableAudit';
	protected $primaryKey = 'id';
	
	public function user()
	{
	    return $this->belongsTo('User', 'created_by', 'id');
	}
	
    function isValidTimeStamp($timestamp)
    {
        return ((string) (int) $timestamp === $timestamp) 
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= 1000000000);
    }

	public function newCollection(array $models = array())
	{
	
	    foreach($models as $model)
	    {
	         
	        $model->data_from_full = ($this->isValidTimeStamp($model->data_from) ? date("Y-m-d H:i:s", $model->data_from) : $model->data_from);
	        $model->data_to_full = ($this->isValidTimeStamp($model->data_to) ? date("Y-m-d H:i:s", $model->data_to) : $model->data_to);
	        $model->data_from = str_limit($model->data_from_full, 30);
	        $model->data_to = str_limit($model->data_to_full, 30);
            $model->microtime = date("Y-m-d H:i:s.u", $model->created_at_micro);
	         
	    }
	
	
	    return new Collection($models);
	}
	
} 