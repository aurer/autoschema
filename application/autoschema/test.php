<?php


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