<?php

AutoSchema::table('bills', function($table)
{
    $table->increments('id');
    $table->integer('user_id')->label('User')->values('users:id,username');
    $table->string('titles')->rules('required');
    $table->string('name')->rules('required');
    $table->text('comments');
    $table->integer('test');
    $table->decimal('amount', 10, 2);
    $table->string('recurrence'); // Weekly, Monthly or Yearly
    $table->integer('renews_on'); // Weekly = day of week, Monthly/Yearly = day of year
    $table->boolean('send_reminder'); // Should an email reminder be sent
    $table->integer('reminder'); // How many days before renewal to send reminder
    $table->boolean('include_in_totals');
    $table->timestamps();
}, function($settings){
    $settings->title('The Bills');
    $settings->title_columns('titles', 'name', 'test');
    $settings->options(array(
        'show_headings' => true,
    ));
});

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

AutoSchema::table('test_table', function($table)
{
    $table->increments('id');
    $table->string('a_string')          ->label('String')   ->rules('required|unique');
    $table->integer('an_integer')       ->label('Integer')  ->rules('required');
    $table->float('a_float', 10, 2)     ->label('Float')    ->rules('');
    $table->decimal('a_decimal', 10, 2) ->label('Decimal')  ->rules('required');
    $table->text('a_text')              ->label('Text')     ->rules('required');
    $table->boolean('a_boolean')        ->label('Boolean')  ->rules('required');
    $table->date('a_date')              ->label('Date')     ->rules('required');
    $table->timestamp('a_timestamp')    ->label('Timestamp')->rules('required');
    $table->blob('a_blob')              ->label('Blob')     ->rules('required');
    $table->timestamps();
});

AutoSchema::view('users_vw', function($view){
    $view->definition("SELECT * FROM bills");
});