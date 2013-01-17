<?php namespace AutoSchema\Drivers;

use \AutoSchema\AutoSchema;

abstract class Driver{
	
	public static function create( $table ){}

	protected static function column_definition( $column ){}

	protected static function columns_in_table( $table ){}

}