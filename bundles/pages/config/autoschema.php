<?php

AutoSchema::table('pages', function($table)
{
    $table->increments('id');
    $table->string('pagetitle');
    $table->string('menutitle');
    $table->string('slug');
    $table->text('content');
    $table->integer('parent');
    $table->integer('depth');
    $table->boolean('visible');
    $table->boolean('active');
    $table->timestamps();
}, function($settings){
    $settings->title_columns('pagetitle', 'slug');
});