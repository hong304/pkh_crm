<?php

class datawarehouse_customer extends Eloquent  {

    public function Customer()
    {
        return $this->hasOne('Customer','customerId','customer_id');
    }



}