<?php

AutoSchema::table('bills', function($table)
{
    $table->increments('id');
    $table->integer('user_id')->label('User')->values('users:id,username');
    $table->string('titles')->rules('required');
    $table->string('name')->rules('required');
    //$table->text('comments');
    $table->integer('test');
    $table->string('amount', 11);
    $table->string('recurrence'); // Weekly, Monthly or Yearly
    $table->integer('renews_on'); // Weekly = day of week, Monthly/Yearly = day of year
    $table->boolean('send_reminder'); // Should an email reminder be sent
    $table->integer('reminder'); // How many days before renewal to send reminder
    $table->boolean('include_in_totals');
    $table->timestamps();
});

AutoSchema::table('users', function($table)
{
    $table->increments('id');
    $table->string('forename', 255);
    $table->string('surname', 255);
    $table->string('username', 255);
    $table->string('email', 255);
    $table->string('password', 255);
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
});

AutoSchema::view('users_vw', function($view){
    $view->definition("SELECT * FROM bills");
});