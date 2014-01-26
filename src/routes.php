<?php

/*
	The main autoschema tables
*/
Route::get('autoschema', function()
{
	Autoschema::load_definitions();
	$data['tables'] = Autoschema::check_tables();
	$data['views'] = Autoschema::check_views();
	return View::make('autoschema::index')->with($data);
});

/*
	Create a table
*/
Route::get('autoschema/create_table/{name}', function($table)
{
	$result = Autoschema::create_table($table);
	return Redirect::back();
});

/*
	Creat a view
*/
Route::get('autoschema/create_view/{name}', function($name)
{
	$result =Autoschema::create_view($name);
	return Redirect::back();
});

/*
	Drop a table
*/
Route::get('autoschema/drop_table/{name}', function($table)
{
	$result = Autoschema::drop_table($table);
	return Redirect::back();
});

/*
	Drop a view
*/
Route::get('autoschema/drop_view/{name}', function($view)
{
	$result = Autoschema::drop_view($view);
	return Redirect::back();
});

/*
	Update a table
*/
Route::get('autoschema/update_table/{name}', function($table)
{
	$result = Autoschema::update_table($table);
	return Redirect::back();
});

/*
	Update a view
*/
Route::get('autoschema/update_view/{name}', function($view)
{
	$result = Autoschema::update_view($view);
	return Redirect::back();
});

/*
	View Backups
*/
Route::get('autoschema/backups', function()
{	
	//dd( AutoBackup::backup('bills') );
	dd( AutoBackup::restore('bills') );
});













