<?php

//use Illuminate\Database\Eloquent\SoftDeletingTrait;

class InvoicePrintFormat extends Eloquent  {

    //use SoftDeletingTrait; 
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'invoiceprintformat';
	protected $primaryKey = 'ipfId';
	//public $timestamps = false;
	
	
	
}