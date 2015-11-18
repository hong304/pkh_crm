<?php

class Receiving extends Eloquent {

    public static function boot()
    {
        parent::boot();

        static::updating(function($table)  {
            $table->updated_by = Auth::user()->id;
        });
        static::saving(function($table)  {
            $table->updated_by = Auth::user()->id;
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
