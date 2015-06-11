<?php

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request)
{
	
	$uri = Request::path();
	
	//dd($_SERVER);
	if($_SERVER['HTTP_HOST'] == 'pkh-b.sylam.net')
	{
        @header('Access-Control-Allow-Origin: http://pkh-f.sylam.net');
        $_SERVER['env'] = 'private_production';
        $_SERVER['frontend'] = 'http://pkh-f.sylam.net';
        $_SERVER['backend'] = 'http://pkh-b.sylam.net';
	}
    elseif($_SERVER['HTTP_HOST'] == 'backend.sylam.net'){
        @header('Access-Control-Allow-Origin: http://frontend.sylam.net');
        $_SERVER['env'] = 'production';
        $_SERVER['frontend'] = 'http://frontend.sylam.net';
        $_SERVER['backend'] = 'http://backend.sylam.net';
    }
	else
	{
	   @header('Access-Control-Allow-Origin: http://yatfai-f.cyrustc.net');
        $_SERVER['env'] = 'test';
        $_SERVER['frontend'] = 'http://yatfai-f.cyrustc.net';
        $_SERVER['backend'] = 'http://yatfai.cyrustc.net';
	}
	
	
	@header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
	@header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
	@header('Access-Control-Allow-Credentials: true');

	//dd($_SERVER);
});


App::after(function($request, $response)
{
	//
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/


Route::filter('auth', function()
{
	if (Auth::guest()) {
		return Redirect::guest('credential/auth');
	}
});


Route::filter('auth.basic', function()
{
	return Auth::basic();
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function()
{
	if (Auth::check()) return Redirect::to('/');
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function()
{
	if (Session::token() != Input::get('_token'))
	{
		throw new Illuminate\Session\TokenMismatchException;
	}
});