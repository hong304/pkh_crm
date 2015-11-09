<?php

class Receiving extends Eloquent {

    public function adjust()
    {
        return $this->hasMany('adjust', 'receivingId', 'receivingId');
    }

    public function purchaseorder()
    {
        return $this->belongsTo('Purchaseorder', 'poCode','poCode');
    }
    
    public function product()
    {
        return $this->hasOne('Product', 'productId','productId');
    }

}
