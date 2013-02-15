<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/
Route::get('test', function(){

	//print_r(Bundle::$bundles);
	AutoSchema::load_definitions();
	//print_r(AutoSchema::get_table_definition('pages'));
});

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


/*
Route::get('/', function()
{
	return View::make('home.index');
});
*/


Route::get('admin', function(){
	$data['tables'] = AutoSchema::tables_in_definition();
	return View::make('admin/index')->with($data);
});

Route::get('admin/(:any)', function($table)
{
	$data['table'] = $table;
	$data['items'] = DB::table($table)->order_by('id')->get();
	$data['title_columns'] = AutoSchema::get_table_definition($table)->settings->title_columns;
	return View::make('admin/list')->with($data);
});

Route::get('admin/(:any)/add', function($table)
{
	$data['table'] = $table;
	$fields = AutoSchema::get_table_definition($table)->columns;
	$data['rules'] = AutoSchema\AutoForm::table_rules($table);
	foreach ($fields as $value) {
		if( !in_array($value['name'], array('id', 'created_at', 'updated_at') ) ){
			$data['fields'][] = $value;
		}
	}
	return View::make('admin/add')->with($data);
});

Route::post('admin/(:any)/add', function($table)
{
	$data['table'] = $table;
	$data['rules'] = AutoSchema\AutoForm::table_rules($table);
	$input = AutoSchema\AutoForm::filter_input($table, Input::all());
	$validation = Validator::make($input, $data['rules']);
	if ($validation->fails())
	{
	    return Redirect::back()->with_input()->with_errors($validation);
	}
	else
	{
		if( isset($input['id'] ) ){
			DB::table($table)->where('id', '=', $input['id'])->update($input);
		}
		else{
			DB::table($table)->insert($input);
		}
	}
	return Redirect::to('/admin/'.$data['table']);
});

Route::get('admin/(:any)/(:num)/edit', function($table, $id)
{
	$data['table'] = $table;
	$data['id'] = $id;
	$data['item'] = DB::table($table)->where_id($id)->first();
	$data['rules'] = AutoSchema\AutoForm::table_rules($data['table']);
	$fields = AutoSchema::get_table_definition($table)->columns;
	foreach ($fields as $value) {
		if( !in_array($value['name'], array('id', 'created_at', 'updated_at') ) ){
			$data['fields'][] = $value;
		}
	}
	Log::notice('Test');
	return View::make('admin/edit')->with($data);
});

Route::any('admin/(:any)/(:num)/update', function($table, $id)
{
	$data['table'] = URI::segment(2);
	$data['id'] = URI::segment(3);
	$rules = AutoSchema\AutoForm::table_rules($table);	
	$input = AutoSchema\AutoForm::filter_input($table, Input::all());
	$validation = Validator::make($input, $rules);
	if ($validation->fails())
	{
	    return Redirect::back()->with_input()->with_errors($validation);
	}
	else
	{
		DB::table($table)->where_id($id)->update($input);
	}
	return Redirect::to("/admin/$table");
});

Route::get('admin/(:any)/(:num)/delete', function($table, $id){
	DB::table($table)->delete($id);
	return Redirect::to("/admin/$table");
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