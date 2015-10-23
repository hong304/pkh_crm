<?php

class invoiceaudit extends Eloquent
{
    public function user()
    {
        return $this->belongsTo('User', 'created_by', 'id');
    }
}
