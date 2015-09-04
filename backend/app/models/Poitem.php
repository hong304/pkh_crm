<?php

class Poitem extends Eloquent {
        public static function boot() {
        parent::boot();

// create a event to happen on updating
        static::updating(function($table)  {
            $table->updated_by = Auth::user()->id;
        });

// create a event to happen on deleting
        static::deleting(function($table)  {
            $table->deleted_by = Auth::user()->id;
        });

// create a event to happen on saving
        static::saving(function($table)  {
            $table->created_by = Auth::user()->id;
        });
}
}
