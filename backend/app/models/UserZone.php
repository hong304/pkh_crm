<?php


class UserZone extends Eloquent  {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'userzone';
	
	public static function getMyZone()
	{
	    $zones = UserZone::select('zoneId')->where('userId', Auth::user()->id)->get();
	    foreach($zones as $z)
	    {
	        $zone[] = $z->zoneId;
	    }
	    return $zone;
	}
	
	public function zoneDetail()
	{
	    return $this->hasOne('Zone', 'zoneId', 'zoneId');
	}
}