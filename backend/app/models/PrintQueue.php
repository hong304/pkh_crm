<?php


class PrintQueue extends Eloquent  {

    //use SoftDeletingTrait;
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'PrintQueue';
	protected $primaryKey = 'job_id';
	//public $timestamps = false;
	
	public static function boot()
	{
	    parent::boot();
	
	    PrintQueue::updated(function($model)
	    {
	        foreach($model->getDirty() as $attribute => $value){
	            $original= $model->getOriginal($attribute);

	            if(!in_array($attribute, array('created_by', 'created_at', 'updated_at', 'updated_by')))
	            {
    	            $x = new TableAudit();
    	            $x->referenceKey = $model->job_id;
    	            $x->table = "PrintQueue";
    	            $x->attribute = $attribute;
    	            $x->data_from = $original;
    	            $x->data_to = $value;
    	            $x->created_by = Auth::user()->id;
    	            $x->created_at_micro = microtime(true);
    	            $x->save();
	            }
	        }
	    });
	}
	
	
	
}