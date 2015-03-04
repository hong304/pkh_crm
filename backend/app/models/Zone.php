<?php

class Zone extends Eloquent  {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'Zone';
	
	public static function getIdByName($name)
	{
	    $zone = Zone::where('zone', $name)->first();
	    dd($name);
	    return $zone->zoneId;
	} 
	
	public static function setCurrentZone($zoneid)
	{
	    Session::put('zone', $zoneid);
	    return true;
	}
}