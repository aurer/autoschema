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