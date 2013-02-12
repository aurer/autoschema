<?php

class Create_Pages {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('pages', function($table)
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
		});
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('pages');
	}

}