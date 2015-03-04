<?php


class SecurityLog extends Eloquent  {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'security_log';
	
	public $timestamps = false;
	
    
	public static function write($message, $additional = false)
	{
	    
	    // get real ip behind cloudflare
	    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
	        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
	    }
	    
	    // format additional information
	    if($additional AND (is_array($additional) OR is_object($additional)))
	    {
	        $additional = json_encode($additional);
	    }
	    
	    // get last caller from debugtrace
	    $trace = debug_backtrace();
	    
	    // prepare logging information
	    $logitem = new SecurityLog();
	    $logitem->userid = Auth::check() ? Auth::user()->id : NULL;
	    $logitem->datetime = time();
	    $logitem->message = $message;
	    $logitem->systeminfo = json_encode(Input::all());
	    $logitem->additional = $additional ? $additional : NULL;
	    $logitem->backtrace = @$trace[1]['function'].'@'.@$trace[1]['class'];
	    $logitem->ip = $_SERVER['REMOTE_ADDR'];
        
	    $logitem->save();
	    
	}
}