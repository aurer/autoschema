<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

Route::any('/convert', function(){
	
	if( Input::file('file') )
	{
		$result = AutoSchemaConverter::convert_file( Input::file('file.tmp_name') );
		return View::make('convert.index')->with('result', $result);
	}
	else
	{
		return View::make('convert.index');
	}
});


Route::get('/', function()
{
	return View::make('home.index');
});

Route::get('admin', function(){
	$data['tables'] = AutoSchema::tables_in_definition();
	return View::make('admin/index')->with($data);
});

Route::get('admin/(:any)', function()
{
	$data['table'] = URI::segment(2);
	$fields = AutoSchema::get_table_definition($data['table'])->columns;
	foreach ($fields as $value) {
		if( !in_array($value['name'], array('id', 'created_at', 'updated_at') ) ){
			$data['fields'][] = $value;
		}
	}
	return View::make('admin/table')->with($data);
});

Route::post('admin/(:any)/add', function()
{
	$data['table'] = URI::segment(2);
	$rules = AutoSchema\AutoForm::table_rules($data['table']);	
	$input = Input::all();
	$validation = Validator::make($input, $rules);
	if ($validation->fails())
	{
	    return Redirect::back()->with_input()->with_errors($validation);
	}
	else
	{
		if( isset($input['id'] ) ){
			DB::table($data['table'])->where('id', '=', $input['id'])->update($input);
		}
		else{
			DB::table($data['table'])->insert($input);
		}
	}
	Redirect::back();
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