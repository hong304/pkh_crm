<?php

class Printlog extends Eloquent  {

    protected $table = 'printlogs';

    public function zone()
    {
        return $this->hasOne('Zone', 'zoneId', 'target_path');
    }


}