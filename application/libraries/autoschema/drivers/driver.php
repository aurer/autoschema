<?php namespace AutoSchema\Drivers;

use \AutoSchema\AutoSchema;

interface Driver{
	
	public static function create_table( $table );

	public static function create_view( $view );

	public static function drop_table( $table );

	public static function drop_view( $view );

	public static function column_definition( $column=array() );

	public static function tables_in_database( );

	public static function views_in_database( );

	public static function columns_in_table( $table );

	public static function update_table( $table );

	public static function update_view( $view );

}