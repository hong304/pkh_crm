<?php

class Container extends Eloquent  {
    

    public function containerproduct(){
        return $this->hasMany('containerproduct');
    }

    public function receive()
    {
        return $this->hasMany('Receiving', 'containerId', 'containerId')
            ->join('Product', 'receivings.productId','=', 'product.productId');
    }

    public function shipping() //containers
    {
        return $this->belongsTo('shipping', 'shippingId', 'shippingId');
    }
}
