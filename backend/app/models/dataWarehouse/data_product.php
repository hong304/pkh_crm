<?php

class data_product extends Eloquent  {



    public function datawarehouse_product(){
        return $this->hasMany('datawarehouse_product');
    }
}