<?php

class Payment extends Eloquent  {

    public function Invoice(){
        return $this->belongsToMany('Invoice')->withPivot('amount','paid')->withTimestamps();
    }


}