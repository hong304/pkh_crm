<?php

//use Illuminate\Database\Eloquent\SoftDeletingTrait;

class role extends Eloquent  {

    //use SoftDeletingTrait;
    
public function user(){
    return $this->belongsToMany('user')->withTimestamps();
}

    public function permissions(){
        return $this->belongsToMany('permissions')->withTimestamps();
    }

} 