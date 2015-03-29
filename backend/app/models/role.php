<?php

//use Illuminate\Database\Eloquent\SoftDeletingTrait;

class role extends Eloquent  {

    //use SoftDeletingTrait;
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
public function user(){
    return $this->belongsToMany('user')->withTimestamps();
}

} 