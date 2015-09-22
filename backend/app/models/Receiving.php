<?php

class Receiving extends Eloquent {

    public function adjust()
    {
        return $this->hasMany('adjust', 'receivingId', 'receivingId');
    }

}
