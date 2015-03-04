<?php

//use Illuminate\Database\Eloquent\SoftDeletingTrait;

class InvoicePrintFormat extends Eloquent  {

    //use SoftDeletingTrait; 
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'InvoicePrintFormat';
	protected $primaryKey = 'ipfId';
	//public $timestamps = false;
	
	
	
}