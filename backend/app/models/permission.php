<?php

class permission extends Eloquent  {

    public function role(){
        return $this->belongsToMany('role')->withTimestamps();
    }


}