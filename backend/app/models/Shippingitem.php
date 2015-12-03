<?php

class Shippingitem extends Eloquent  {
    
    public function productDetail()
    {
        return $this->hasOne('Product', 'productId', 'productId');
    }
    
    public function receive()
    {
        return $this->hasMany('Receiving', 'containerId', 'containerId')
            ->join('Product', 'receivings.productId','=', 'product.productId');
    }
}
