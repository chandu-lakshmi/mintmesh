<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/
/* Route related to API docs */
Route::get('docs', function(){
	return View::make('docs.v1.index');
});

/* Home page of Mintmesh webservice */
Route::get('/', function()
{
	return View::make('hello');
});

// Sample APIs for testing
//Route::group(array('prefix' => 'v1'), function() {
Route::group(array('prefix' => 'v1', 'before' => 'oauth'), function() {
	Route::get("movies", "Samples\SampleController@index");

	Route::post("movies/create", "Samples\SampleController@create");
});


/* Custom route for 404 page not found */
App::missing(function($exception)
{
    return Response::json(array('status_code'=>'404', 'status'=>'error', 'message'=>'Page not found', 'data'=>[]));
});

/* Route for getting the access toker with end users oAuth2.0
|  Frst Time:
|  POST: username & password
|  returns accesstoken
|  Next time: (When access token expires, use the refresh token to get new access token)
|  POST : refresh token
|  return accesstoken
*/
Route::post("oauth/access_token", function() {
	return Response::json(Authorizer::issueAccessToken());
});


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Below routes are only specific for Admin panel
| This uses some default credentials for testing and bypassing user authentication
| and gains some special previleges
|
*/

Route::group(array("prefix" => "admin"), function() {
	Route::get("login", 'Admin\LoginController@index');
});