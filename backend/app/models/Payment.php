<?php

class Payment extends Eloquent  {

    public function Customer()
    {
        return $this->hasOne('Customer','customerId','customerId');
    }



}