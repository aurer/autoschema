<?php

AutoSchema::table('users', function($table)
{
    $table->increments('id');
    $table->string('forename', 255)->rules('required');
    $table->string('surname', 255)->rules('required');
    $table->string('username', 255)->rules('required');
    $table->string('email', 255)->rules('required|email');
    $table->string('password', 255)->rules('required');
    $table->string('hash', 255);
    $table->boolean('active');
    $table->timestamps();
});

AutoSchema::table('emails', function($table)
{
    $table->increments('id');
    $table->string('user_id', 255);
    $table->string('email', 255);
    $table->boolean('active');
    $table->timestamps();
}, function($settings){
    $settings->title_columns('email', 'active');
});

AutoSchema::view('users_vw', function($view){
    $view->definition("SELECT * FROM bills");
});