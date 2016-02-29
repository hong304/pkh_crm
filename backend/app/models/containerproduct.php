<?php

class containerproduct extends Eloquent{

    public function product()
    {
        return $this->hasOne('Product', 'productId', 'productId');
    }

    public function container() //containers
    {
        return $this->belongsTo('shippingitem','shippingitem_id','id');
    }

}