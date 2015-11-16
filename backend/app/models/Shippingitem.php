<?php

class Shippingitem extends Eloquent  {
    
    public function productDetail()
    {
        return $this->hasOne('Product', 'productId', 'productId');
    }
}
