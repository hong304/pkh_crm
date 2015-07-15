<?php

class lastitem extends Eloquent  {
    protected $table = 'lastitem';

    public function getUpdatedAtAttribute($attr) {
        if($attr > 10000)
            return date("Y-m-d h:i:s A", $attr);
        else
            return date("Y-m-d h:i:s A", strtotime($attr));
    }
}