<?php namespace AutoSchema;

use \Laravel\Log as Log;
use \Laravel\Config as Config;
use \Laravel\Cache as Cache;
use \Laravel\Request as Request;
use \Laravel\Database as DB;

class AutoSchema
{
	protected static $tables = array();
	protected static $views = array();
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
	 * Define an AutoSchema view.
	 *
	 * @param  string 	 $table
	 * @param  function  $callback
	 * @return Definition
	 */
	public static function define_view($view, $callback)
	{
		$name = $view;
		call_user_func($callback, $view = new View($view));
		static::$views[$name] = $view;
		return $view;
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
	 * Get the view names from the cached definitions.
	 *
	 * @return string
	 */
	public static function views_in_definition()
	{
		return array_keys( static::get_views() );
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
			return new Table($tables[$table]);
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

		$schema = static::get($table);
		if( !$schema ) return false;
		foreach ($schema->columns as $key => $column) {
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
		$database 	= static::tables_in_database();
		$definition = static::tables_in_definition();
		$result 	= array();
		
		// Work out which tables are where
		$in_both 			= array_intersect($definition, $database); // tables in both the definition and database
		$just_definition 	= array_diff($definition, $database); // tables only in the definition
		$just_database 		= array_diff($database, $definition); // tables only in the database		
		$all_tables 		= array_merge($in_both, $just_definition, $just_database);
		sort($all_tables);

		foreach ($all_tables as $key => $table) {
			$obj 				= new \stdClass();
			$obj->name 			= $table;
			$obj->valid 		= true;
			$obj->errors 		= array();
			$obj->error_type	= '';

			if( in_array($table, $just_definition) ){
				$obj->valid 		= false;
				$obj->errors[] 		= 'This table does not exist yet.';
				$obj->error_type 	= 'missing_from_database';
			}
			elseif( in_array($table, $just_database) ){
				$obj->valid 		= false;
				$obj->errors[] 		= 'This table has not defined in the site.';
				$obj->error_type 	= 'missing_from_definition';
			}
			
			// If we have errors, there's no need to check the table so we continue.
			if( !$obj->valid ){
				$result[] = $obj;
				continue;
			}

			// Check the table columns for changes 
			$errors = static::table($table)->check();
			if( count($errors) > 0){
				$obj->errors 		= $errors;
				$obj->valid 		= false;
				$obj->error_type 	= 'schema_error';
			}
			$result[] = $obj;
		}
		return $result;
	}

	public static function check_views()
	{
		//$database 	= static::tables_in_database();
		$definition = static::views_in_definition();
		$result 	= array();
		return $definition;
	}

	public static function table($table)
	{
		$definition = static::get($table);
		foreach ($definition as $key => $value) {
			$obj = new \stdClass;
			$obj->name = $value;
		}
		return new Table($definition);
	}

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
		Cache::forget('autoschema_schema_views');
		Cache::forever('autoschema_schema_views', static::$views);
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
	 * Get the cached AutoSchema definitions.
	 *
	 * @return array
	 */
	public static function get_views()
	{
		return Cache::get('autoschema_schema_views');
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
		$schema = static::get($table);
		
		if( !$schema ) return false;

		
		foreach ($schema->columns as $column) {
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
		if( in_array($driver, array('mysql', 'pgsql') ) ){
			switch ($driver) {
				case 'mysql':
					return new Drivers\MySQL;
					break;
				case 'pgsql':
					return new Drivers\Postgres;
					break;
			}
			return new Drivers\MySql;
		} else {
			Log::error('AutoSchema: only mysql and pgsql databases are supported at the moment.');
			exit('AutoSchema: only mysql and pgsql databases are supported at the moment.');	
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
