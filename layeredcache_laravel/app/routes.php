<?php
//0290452818 chris
//0292486277 harvey
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


/*
//some route health check
Route::get('/', function()
{
	return 'Hello World';
	//return View::make('hello');
});

//some route health check
Route::get('hello', function()
{
	return 'Hello World';
	//return View::make('hello');
});
*/

//some introdutory pages goes here
Route::get('index', 'HomeController@showWelcome');
Route::controller('home', 'HomeController');


//route to controler in charge of data access speed test (caching)
Route::controller('accessTime', 'CachedDataAccessController');
//Route::post('accessTimeAjaxDirectly', 'CachedDataAccessController@postDirectlyAjax');