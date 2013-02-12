<?php

Route::get('(.*)', array('as' => 'master', 'before' => 'init', function($url){
	return Pages::render($url);
}));