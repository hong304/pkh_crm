<?php


class broadcastMessage extends Eloquent  {


    public function broadcastMessageRead(){
        return $this->hasMany('broadcastMessageRead');
    }
} 