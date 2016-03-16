<?php

class vanHeader extends Eloquent
{

    public static function boot()
    {
        parent::boot();

        vanHeader::updating(function($model)
        {

            foreach($model->getDirty() as $attribute => $value){
                $original= $model->getOriginal($attribute);
                $x = new vanHeaderAudit();
                $x->attribute = $attribute;
                $x->zoneId = $model->zoneId;
                $x->shift = $model->shift;
                $x->data_from = $original;
                $x->data_to = $value;
                $x->created_by = Auth::user()->id;
                $x->ip = $_SERVER['REMOTE_ADDR'];
                $x->save();
            }

        });

    }
}
