<?php

use Illuminate\Database\Eloquent\SoftDeletingTrait;

class invoiceitemBatch extends Eloquent
{
    use SoftDeletingTrait;
    protected $dates = ['deleted_at'];
}
