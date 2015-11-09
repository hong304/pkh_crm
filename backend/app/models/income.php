<?php

class income extends Eloquent  {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */

    public static function boot() {
        parent::boot();
        static::updating(function($table)  {
            $table->updated_by = Auth::user()->id;
        });
        static::saving(function($table)  {
            $table->updated_by = Auth::user()->id;
        });

    }



  public function getUpdatedByTextAttribute() {
       return Config::get('userName.'.$this->updated_by);;
   }

}