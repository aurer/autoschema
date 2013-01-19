<?php

AutoSchema::define('bills', function($table)
{
    $table->increments('id');
    $table->integer('user_id');
    $table->string('title');
    $table->string('name');
    $table->text('comment');
    $table->string('amounts');
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
    $table->string('forename', 255);
    $table->string('surname', 255);
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

AutoSchema::define('bills2', function($table)
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

AutoSchema::define('bills3', function($table)
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

for($i=0; $i<60; $i++){
    AutoSchema::define('bills'.$i, function($table)
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
        $table->string('col1');
        $table->string('col2');
        $table->string('col3');
        $table->string('col4');
        $table->string('col5');
        $table->string('col6');
        $table->string('col7');
        $table->string('col8');
        $table->string('col9');
        $table->string('col10');
        $table->string('col11');
        $table->string('col12');
        $table->string('col13');
        $table->string('col14');
        $table->string('col15');
        $table->string('col16');
        $table->string('col17');
        $table->string('col18');
        $table->string('col19');
        $table->string('col20');
        $table->string('col21');
        $table->string('col22');
        $table->string('col23');
        $table->string('col24');
        $table->string('col25');
        $table->string('col26');
        $table->string('col27');
        $table->string('col28');
        $table->string('col29');
        $table->string('col30');
        $table->string('col31');
        $table->timestamps();
    });
}