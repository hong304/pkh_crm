<?php


class adjust extends Eloquent  {


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

    public function newReceiving()
    {
        return $this->hasOne('Receiving', 'adjustId', 'adjustId');
    }

    public function receiving(){
        return $this->belongsTo('receiving','receivingId','receivingId');
    }

    public function getUpdatedByAttribute($attr) {
        return Config::get('userName.'.$attr);
    }

}