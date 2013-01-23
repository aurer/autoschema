<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

Route::get('test', function(){

});

Route::get('/', function()
{
	return View::make('home.index');
});

Route::get('/autoschema', function()
{
	AutoSchema::load_definitions();
	$data['tables'] = AutoSchema::check_tables();
	$data['views'] = AutoSchema::check_views();
	return View::make('autoschema.index')->with($data);
});

Route::get('/autoschema/views', function()
{
	AutoSchema::load_definitions();
	print_r( AutoSchema::get_views() );
});

Route::get('autoschema/create_table/(:any)', function($table)
{
	$result = AutoSchema::create_table($table);
	return Redirect::back();
});

Route::get('autoschema/create_view/(:any)', function($name)
{
	$result =AutoSchema::create_view($name);
	return Redirect::back();
});

Route::get('autoschema/drop_table/(:any)', function($table)
{
	$result = AutoSchema::drop_table($table);
	return Redirect::back();
});

Route::get('autoschema/update_table/(:any)', function($table)
{
	$result = AutoSchema::update_table($table);
	return Redirect::back();
});

Route::get('autoschema/update_view/(:any)', function($view)
{
	$result = AutoSchema::update_view($view);
	return Redirect::back();
});


Route::get('/form', function()
{
	if( Input::get('table') ){
		$data['fields'] = AutoSchema::get_for_form(Input::get('table'));
	} else {
		$data['fields'] = array();
	}
	
	return View::make('form.index')->with($data);
});

Route::post('/form', function()
{
	$fields = AutoSchema::get_for_form('users');
	foreach ($fields as $key => $value) {
		$data[$value['name']] = Input::get($value['name']);
	}
	$result = DB::table('users')->insert($data);
});
/*
|--------------------------------------------------------------------------
| Application 404 & 500 Error Handlers
|--------------------------------------------------------------------------
|
| To centralize and simplify 404 handling, Laravel uses an awesome event
| system to retrieve the response. Feel free to modify this function to
| your tastes and the needs of your application.
|
| Similarly, we use an event to handle the display of 500 level errors
| within the application. These errors are fired when there is an
| uncaught exception thrown in the application.
|
*/

Event::listen('404', function()
{
	return Response::error('404');
});

Event::listen('500', function()
{
	return Response::error('500');
});

/*
|--------------------------------------------------------------------------
| Route Filters
|--------------------------------------------------------------------------
|
| Filters provide a convenient method for attaching functionality to your
| routes. The built-in before and after filters are called before and
| after every request to your application, and you may even create
| other filters that can be attached to individual routes.
|
| Let's walk through an example...
|
| First, define a filter:
|
|		Route::filter('filter', function()
|		{
|			return 'Filtered!';
|		});
|
| Next, attach the filter to a route:
|
|		Route::get('/', array('before' => 'filter', function()
|		{
|			return 'Hello World!';
|		}));
|
*/

Route::filter('before', function()
{
	// Do stuff before every request to your application...
});

Route::filter('after', function($response)
{
	// Do stuff after every request to your application...
});

Route::filter('csrf', function()
{
	if (Request::forged()) return Response::error('500');
});

Route::filter('auth', function()
{
	if (Auth::guest()) return Redirect::to('login');
});