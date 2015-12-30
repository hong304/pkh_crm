<?php

class Receiving extends Eloquent {

    public $timestamps = false;


    public static function boot()
    {
        parent::boot();

        receiving::updated(function($model)
        {
            foreach($model->getDirty() as $attribute => $value){
                $original= $model->getOriginal($attribute);
                //echo "Changed $attribute from '$original' to '$value'<br/>";

                    $x = new receivingAudit();
                    $x->productId = $model->productId;
                    $x->receivingId = $model->receivingId;
                    $x->attribute = $attribute;
                    $x->data_from = $original;
                    $x->data_to = $value;
                    $x->created_by = Auth::user()->id;
                    $x->ip = $_SERVER['REMOTE_ADDR'];
                    $x->save();


            }

        });

    }

    public function adjust()
    {
        return $this->hasMany('adjust', 'receivingId', 'receivingId');
    }

    public function purchaseorder()
    {
        return $this->belongsTo('Purchaseorder', 'poCode','poCode');
    }
    
    public function product()
    {
        return $this->hasOne('Product', 'productId','productId');
    }
    public function getUpdatedByAttribute($attr) {
        return Config::get('userName.'.$attr);
    }
}
