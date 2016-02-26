<?php

class containerproduct extends Eloquent{

    public function product()
    {
        return $this->hasOne('Product', 'productId', 'productId');
    }

}