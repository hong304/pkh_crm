<?php

class Supplier extends Eloquent  {
   
    public function country()
    {
        return $this->hasMany('Country', 'countryId', 'countryId');
    }

    public function purchaseorder(){
        return $this->hasMany('Purchaseorder','supplierCode','supplierCode');
    }

    public function receiving(){
        return $this->hasManyThrough('Receiving','Purchaseorder','supplierCode','poCode');
    }


}