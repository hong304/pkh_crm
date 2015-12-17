<?php


class adjust extends Eloquent  {

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