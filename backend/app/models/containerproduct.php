<?php

class containerproduct extends Eloquent{

    public function product()
    {
        return $this->hasOne('Product', 'productId', 'productId');
    }

    public function container() //containers
    {
        return $this->belongsTo('Container','container_id','id');
    }

}