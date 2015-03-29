<?php

class datawarehouse_customer extends Eloquent  {

    public function customer()
    {
        return $this->hasOne('customer','customerId','customer_id');
    }



}