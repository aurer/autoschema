<?php namespace AutoSchema;

use \Laravel\Log as Log;
use \Laravel\Config as Config;
use \Laravel\Cache as Cache;
use \Laravel\Request as Request;
use \Laravel\Database as DB;

class AutoSchema
{
	protected static $tables = array();
	protected static $hidden_columns = array('id', 'created_at', 'updated_at');
	
	/**
	 * Define an AutoSchema table.
	 *
	 * @param  string 	 $table
	 * @param  function  $callback
	 * @return Definition
	 */
	public static function define($table, $callback)
	{
		$name = $table;
		call_user_func($callback, $table = new Definition($table));
		static::$tables[$name] = $table->as_array();
		return $table;
	}

	/**
	 * Load any AutoSchema definitions.
	 *
	 * @return integer
	 */
	public static function load_definitions()
	{	
		$configs = 0;
		$paths[] = path('app').'config/';
		if( ! is_null(Request::env() ) ){
			$paths[] = path('app').'config/'. Request::env() . '/';
		}
		foreach ($paths as $path) {
			if( is_file( $path . 'autoschema' . EXT ) ){
				$configs += 1;
				// Run all the autoschema define statements to put the schemas into static::$tables;
				require $path . 'autoschema' . EXT;
			}
		}
		static::cache_definitions();
		return static::$tables;
	}

	/**
	 * Get the table names from the cached definitions.
	 *
	 * @return string
	 */
	public static function tables_in_definition()
	{
		return array_keys( static::get_definitions() );
	}
	
	/**
	 * Get a table schema.
	 *
	 * @param  string   $table
	 * @return array
	 */
	public static function get($table)
	{
		$tables = Cache::get('autoschema_schema');
		if( array_key_exists($table, $tables) ){
			return $tables[$table];
		} else {
			Log::notice("AutoSchema: the '$table' table is not defined");
			return false;
		}
	}

	/**
	 * Get a table schema.
	 *
	 * @param  string   $table
	 * @return array
	 */
	public static function get_for_form($table, $showall=false)
	{
		$columns = array();
		$typecast_html = array(
			'string' 	=> 'text',
			'integer' 	=> 'text',
			'float' 	=> 'text',
			'decimal' 	=> 'text',
			'text' 		=> 'textarea',
			'boolean' 	=> 'checkbox',
			'date' 		=> 'text',
			'timestamp' => 'text',
			'blob' 		=> 'file',
		);

		$schema = static::get($table, $showall);
		if( !$schema ) return false;
		foreach ($schema['columns'] as $key => $column) {
			$column['type'] = $typecast_html[$column['type']];
			
			// Build values array
			if( isset($column['values']) ){
				$values = $column['values'];
				if( is_array($values) ){
					$column['type'] = 'select';
				}
				// Should we get values from a specified table e.g. 'table:column1,column2'
				elseif( preg_match('/^([a-z_]+):([a-z_0-9]+),?([a-z_0-9]+)?$/', $values, $matches) ){
					$table = $matches[1];
					$col1 = $matches[2];
					$col2 = isset($matches[3]) ? $matches[3] : null;
					$result = isset($matches[3]) ? DB::query("SELECT $col1, $col2 FROM $table") : DB::query("SELECT $col1 FROM $table");

					$column['type'] = 'select';
					$column['values'] = array();
					foreach ($result as $key => $value) {
						if( $col2 ) $column['values'][$value->$col1] = $value->$col2;
						else $column['values'][$key] = $value->$col1;
					}
					$column['values'];
				}
			}

			if( ! $showall ){
				if ( ! in_array($column['name'], static::$hidden_columns ) ){
					$columns[$key] = $column;
				}
			} else {
				$columns[$key] = $column;
			}
		}
		
		return $columns;
	}

	/**
	 * Return all tables in cached definition as well as table in the database 
	 * along with a 'valid' boolean and an error message about the status.
	 *
	 * @return array
	 */
	public static function check_tables()
	{
		$database = static::tables_in_database();
		$definition = static::tables_in_definition();
		$tables = array('in_definition'=>array(), 'in_database'=>array(), 'in_both'=>array());
		foreach ($definition as $value) {
			if( !in_array($value, $database) ){
				$tables['in_definition'][] = $value;
			} else {
				$tables['in_both'][] = $value;
			}
		}
		foreach ($database as $value) {
			if( !in_array($value, $definition) ){
				$tables['in_database'][] = $value;
			}
		}

		$merged = array_merge($tables['in_definition'], $tables['in_database'], $tables['in_both']);
		$result = array();
		foreach ($merged as $key => $table) {
			$result[$key]['schema_errors'] 	= array();
			$result[$key]['name'] 			= $table;
			$result[$key]['valid'] 			= true;
			$result[$key]['error'] 			= '';

			if( in_array($table, $tables['in_definition']) ){
				$result[$key]['name'] = $table;
				$result[$key]['valid'] = false;
				$result[$key]['error'] = 'missing_from_database';
			}
			elseif( in_array($table, $tables['in_database']) ){
				$result[$key]['name'] = $table;
				$result[$key]['valid'] = false;
				$result[$key]['error'] = 'missing_from_definition';
			}
			
			// If we have errors, there's no need to check the table so we continue.
			if( !$result[$key]['valid'] ){
				continue;	
			}

			// Check the table columns for changes 
			$result[$key]['schema_errors'] = static::table($table)->check();
			if( count($result[$key]['schema_errors']) > 0){
				$result[$key]['valid'] = false;
				$result[$key]['error'] = 'schema_error';
			}
		}
		sort($result);
		return $result;
	}

	public static function table($table)
	{
		$definition = static::get($table);
		return new Table($definition);
	}

	/*public static function check_table($table)
	{
		$database = static::columns_in_table($table);
		$definition = static::columns_in_definition($table);
		$errors = array();
		foreach ($definition as $key => $value) {
			if( !array_key_exists($key, $database) ){
				$errors['missing_in_database'][] = $key;
			}
			elseif( $value != $database[$key] ){
				$errors['changed'][] = $key;
			}
		}
		foreach ($database as $key => $value) {
			if( !array_key_exists($key, $definition) ){
				$errors['missing_in_definition'][] = $key;
			}
		}
		return $errors;
	}*/

	/**
	 * Cache the AutoSchema definitions.
	 *
	 * @return void
	 */
	public static function cache_definitions()
	{
		if( count(static::$tables) < 1 ){
			Log::error('AutoSchema: Cache failed: not table definitions found in AutoSchema::$tables.');
			return false;
		}
		Cache::forget('autoschema_schema');
		Cache::forever('autoschema_schema', static::$tables);
	}

	/**
	 * Get the cached AutoSchema definitions.
	 *
	 * @return array
	 */
	public static function get_definitions()
	{
		return Cache::get('autoschema_schema');
	}

	/**
	 * Return the columns for a given definition.
	 *
	 * @param  string 	$table
	 * @return array
	 */
	public static function columns_in_definition($table)
	{
		$columns = array();
		$schema = static::get($table, true);
		if( !$schema ) return false;

		foreach ($schema['columns'] as $column) {
			$name = $column['name'];
			$length = isset($column['length']) ? $column['length'] : '';
			$type = $column['type'];
			$columns[$name] = trim("$name $type $length");
		}
		return $columns;
	}

	/**
	 * Create a new driver instance.
	 *
	 * @param  string  $driver
	 * @return Autoschema\Drivers\Driver
	 */
	public static function driver()
	{
		$driver = Config::get('database.default');
		if( $driver === 'mysql' ){
			return new Drivers\MySql;
		} else {
			Log::error('AutoSchema: only mysql databases are supported at the moment.');
			exit('AutoSchema: only mysql databases are supported at the moment, please set your database driver to "mysql".');	
		} 
	}

	/**
	 * Magic Method for calling the methods on the default driver.
	 */
	public static function __callStatic($method, $parameters)
	{
		return call_user_func_array(array(static::driver(), $method), $parameters);
	}

	/*
	public function __set($property, $value){

	}

	public function __get($property){

	}
	*/
}
