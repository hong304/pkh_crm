<?php

class lastitem extends Eloquent  {
    protected $table = 'lastitem';

    public static function boot()
    {
        parent::boot();

        lastitem::updated(function($model)
        {
            foreach($model->getDirty() as $attribute => $value){
                $original= $model->getOriginal($attribute);
                //echo "Changed $attribute from '$original' to '$value'<br/>";

                $x = new lastitemAudit();
                $x->productId = $model->productId;
                $x->customerId = $model->customerId;
                $x->attribute = $attribute;
                $x->data_from = $original;
                $x->data_to = $value;
                $x->created_by = Auth::user()->id;
                $x->ip = $_SERVER['REMOTE_ADDR'];
                $x->save();


            }

        });

    }
}