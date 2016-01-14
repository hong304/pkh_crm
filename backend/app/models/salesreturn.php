<?php

class salesreturn extends Eloquent  {
    public static function boot() {
        parent::boot();

        static::saving(function($table)  {
            $table->created_by = Auth::user()->id;
        });

    }
}