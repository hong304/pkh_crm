<?php


class adjust extends Eloquent  {

    public function newReveiving()
    {
        return $this->hasOne('Receiving', 'adjustId', 'adjustId');
    }

    public function receiving(){
        return $this->belongsTo('receiving','receivingId','receivingId');
    }

}