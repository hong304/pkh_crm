<?php

class vansell extends Eloquent  {
    public function products()
    {
        return $this->hasOne('product', 'productId', 'productId');
    }
}