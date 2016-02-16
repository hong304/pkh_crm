<?php

class van extends Eloquent  {

    public function products()
    {
        return $this->hasOne('Product', 'productId', 'productId');
    }

}