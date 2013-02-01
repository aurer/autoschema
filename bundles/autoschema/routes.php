<?php

/*
	The main autoschema tables
*/
Route::get('(:bundle)', function()
{
	AutoSchema::load_definitions();
	$data['tables'] = AutoSchema::check_tables();
	$data['views'] = AutoSchema::check_views();
	return View::make('autoschema::autoschema/index')->with($data);
});

/*
	Create a table
*/
Route::get('(:bundle)/create_table/(:any)', function($table)
{
	$result = AutoSchema::create_table($table);
	return Redirect::back();
});

/*
	Creat a view
*/
Route::get('(:bundle)/create_view/(:any)', function($name)
{
	$result =AutoSchema::create_view($name);
	return Redirect::back();
});

/*
	Drop a table
*/
Route::get('(:bundle)/drop_table/(:any)', function($table)
{
	$result = AutoSchema::drop_table($table);
	return Redirect::back();
});

/*
	Drop a view
*/
Route::get('(:bundle)/drop_view/(:any)', function($view)
{
	$result = AutoSchema::drop_table($view);
	return Redirect::back();
});

/*
	Update a table
*/
Route::get('(:bundle)/update_table/(:any)', function($table)
{
	$result = AutoSchema::update_table($table);
	return Redirect::back();
});

/*
	Update a view
*/
Route::get('(:bundle)/update_view/(:any)', function($view)
{
	$result = AutoSchema::update_view($view);
	return Redirect::back();
});