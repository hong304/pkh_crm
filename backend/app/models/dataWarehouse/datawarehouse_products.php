<?php

class datawarehouse_product extends Eloquent  {

    public function data_product()
    {
        return $this->belongsto('data_product');
    }



}