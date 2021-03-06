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
	if($_SERVER['HTTP_HOST'] == 'b1.pingkeehong.com')
	{
        @header('Access-Control-Allow-Origin: http://f1.pingkeehong.com');
        $_SERVER['env'] = 'private_production';
        $_SERVER['frontend'] = 'http://f1.pingkeehong.com';
        $_SERVER['backend'] = 'http://b1.pingkeehong.com';
	}
    elseif($_SERVER['HTTP_HOST'] == 'backend.pingkeehong.com'){
        @header('Access-Control-Allow-Origin: http://frontend.pingkeehong.com');
        $_SERVER['env'] = 'production';
        $_SERVER['frontend'] = 'http://frontend.pingkeehong.com';
        $_SERVER['backend'] = 'http://backend.pingkeehong.com';
    }elseif($_SERVER['HTTP_HOST'] == 'uat-b.pinekeehong.com'){
        @header('Access-Control-Allow-Origin: http://uat-f.pingkeehong.com');
        $_SERVER['env'] = 'uat';
        $_SERVER['frontend'] = 'http://uat-f.pingkeehong.com';
        $_SERVER['backend'] = 'http://uat-b.pingkeehong.com';
    }elseif($_SERVER['HTTP_HOST'] == 'live-b.pingkeehong.com'){
        @header('Access-Control-Allow-Origin: http://live-f.pingkeehong.com');
        $_SERVER['env'] = 'uat';
        $_SERVER['frontend'] = 'http://live-f.pingkeehong.com';
        $_SERVER['backend'] = 'http://live-b.pingkeehong.com';
    }
	else
	{
	   @header('Access-Control-Allow-Origin: http://dev-f.pingkeehong.com');
        $_SERVER['env'] = 'test';
        $_SERVER['frontend'] = 'http://dev-f.pingkeehong.com';
        $_SERVER['backend'] = 'http://dev-b.pingkeehong.com';
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