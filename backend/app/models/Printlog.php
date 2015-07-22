<?php

class Printlog extends Eloquent  {

    protected $table = 'printlogs';

    public function getUpdatedAtAttribute($attr) {
        return  date("Y-m-d H:i:s", $attr);

    }

    public function zone()
    {
        return $this->hasOne('Zone', 'zoneId', 'target_path');
    }


}