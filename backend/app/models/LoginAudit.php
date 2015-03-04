<?php

//use Illuminate\Database\Eloquent\SoftDeletingTrait;

class LoginAudit extends Eloquent  {

    //use SoftDeletingTrait;
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
    //protected $with = ['User'];
	protected $table = 'LoginAudit';
	protected $primaryKey = 'id';
	
	public function user()
	{
	    return $this->belongsTo('User', 'user', 'id'); 
	}
	
} 