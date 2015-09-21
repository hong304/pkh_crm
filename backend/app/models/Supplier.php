<?php

class Supplier extends Eloquent  {
   
    public function country()
    {
        return $this->hasMany('Country', 'countryId', 'countryId');
    }
}