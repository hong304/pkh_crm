<?php

class Payment extends Eloquent  {

    public static function boot() {
        parent::boot();
        static::updating(function($table)  {
            $table->updated_by = Auth::user()->id;
        });
        static::saving(function($table)  {
            $table->updated_by = Auth::user()->id;
        });

    }

    public function Invoice(){
        return $this->belongsToMany('Invoice')->withPivot('amount','paid')->withTimestamps();
    }

    public function user(){
        return $this->hasOne('User','id','updated_by');
    }
    public function getUpdatedByNameAttribute(){
        return $this->user->name;
    }
}