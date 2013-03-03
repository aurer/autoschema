<?php require 'AutoSchemaConverter.php';

Route::get('(:bundle)', function(){
	return View::make('autoschema-converter::index');
});

Route::post('(:bundle)', function(){
	
	if( Input::file('file') )
	{
		$result = AutoSchemaConverter::convert_file( Input::file('file.tmp_name') );
		return View::make('autoschema-converter::index')->with('result', $result);
	}
	return Redirect::to('(:bundle)');
});