<?php

AutoSchema::define('bills', function($table)
{
    $table->increments('id');
    $table->integer('user_id');
    $table->string('title');
    $table->string('name');
    $table->text('comments');
    $table->string('amount');
    $table->string('recurrence'); // Weekly, Monthly or Yearly
    $table->integer('renews_on'); // Weekly = day of week, Monthly/Yearly = day of year
    $table->boolean('send_reminder'); // Should an email reminder be sent
    $table->integer('reminder'); // How many days before renewal to send reminder
    $table->boolean('include_in_totals');
    $table->timestamps();
});

AutoSchema::define('users', function($table)
{
    $table->increments('id');
    $table->string('forename', 255)->values('John');
    $table->string('surnames', 255)->values('Smith');
    $table->string('username', 255);
    $table->integer('email')->values('emails:id,email');
    $table->string('communication_pref')->label('Communcation Preferences')->values(array('email'=>'Email', 'phone'=>'Phone', 'mail'=>'Mail'))->attributes(array('formtype'=>'checkbox'));
    $table->string('password', 255);
    $table->string('hash', 255);
    $table->boolean('active');
    $table->timestamps();
});

AutoSchema::define('emails', function($table)
{
    $table->increments('id');
    $table->string('user_id', 255);
    $table->string('email', 255);
    $table->boolean('active');
    $table->timestamps();
});