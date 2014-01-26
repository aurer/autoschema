<?php namespace Aurer\Autoschema;

use Log;
use Config;
use Cache;
use Request;
use DB;

define('DS', DIRECTORY_SEPARATOR);

class Autoschema
{
	protected static $tables = array();
	protected static $settings = array();
	protected static $views = array();
	protected static $hidden_columns = array('id', 'created_at', 'updated_at');
	
	/**
	 * Define an Autoschema table.
	 *
	 * @param  string 	 $table
	 * @param  function  $callback
	 * @return array
	 */
	public static function table($name, $table_callback, $settings_callback=false)
	{
		call_user_func($table_callback, static::$tables[$name] = new Table($name));
		static::$settings = static::generate_settings($name);
		if( $settings_callback ){
			call_user_func($settings_callback, $settings = new Settings($name));
			foreach ($settings as $key => $value) {
				static::$tables[$name]->settings->$key = $value;
			}
		}
		return static::$tables[$name];
	}

	/**
	 ** Generate some admin automatic settings for an Autoschema table.
	 *
	 * @param  string $name
	 * @return void
	 */
	protected static function generate_settings($name)
	{
		$settings = new \stdClass;
		$settings->title = str_replace('_', ' ', ucfirst($name) );
		$settings->title_columns = array();
		for ($i=1; $i < 4; $i++) { 
			$settings->title_columns[] = static::$tables[$name]->columns[$i]['name'];	
		}
		static::$tables[$name]->settings = $settings;
	}

	/**
	 * Define an Autoschema view.
	 *
	 * @param  string 	 $table
	 * @param  function  $callback
	 * @return array
	 */
	public static function view($name, $callback)
	{
		call_user_func($callback, static::$views[$name] = new View($name));
		return static::$views[$name];
	}

	/**
	 * Load any Autoschema definitions.
	 *
	 * @return array
	 */
	public static function load_definitions()
	{	
		$config = Config::get('autoschema');

		$paths[] = app_path() . '/autoschema';

		// Loop over the paths and check for definition files
		foreach ($paths as $path) {
			if( is_dir( $path ) ){
				foreach( scandir($path) as $file ){
					if( ends_with($file, '.php') ){
						require $path . DS . $file;
					}
				}
			}
		}

		// The definition files will have been run to cache the results
		static::cache_definitions();
		return static::$tables;
	}

	/**
	 * Get the table names from the cached definitions.
	 *
	 * @return array
	 */
	public static function tables_in_definition()
	{
		$definitions = static::get_definitions();
		if( $definitions && is_array($definitions->tables) ){
			return array_keys( $definitions->tables );
		}
		return array();
	}

	/**
	 * Get the view names from the cached definitions.
	 *
	 * @return array
	 */
	public static function views_in_definition()
	{
		if( isset(static::get_definitions()->views) && is_array(static::get_definitions()->views) ){
			return array_keys( static::get_definitions()->views );
		}
		return array();
	}
	
	/**
	 * Get a table definition.
	 *
	 * @param  string   $table
	 * @return array
	 */
	public static function get_table_definition($table)
	{
		$tables = Cache::get('autoschema_definitions');
		if( is_array($tables) && array_key_exists($table, $tables) ){
			return $tables[$table];
		} else {
			Log::notice(__METHOD__ . ": the '$table' table is not defined.");
			return false;
		}
	}

	/**
	 * Get a view definition.
	 *
	 * @param  string   $view
	 * @return array
	 */
	public static function get_view_definition($view)
	{
		$views = Cache::get('autoschema_definitions');
		if( is_array($views) && array_key_exists($view, $views) ){
			return $views[$view];
		} else {
			Log::notice(__METHOD__ . ": the '$view' view is not defined");
			return false;
		}
	}

	/**
	 * Get a table schema for use when generating form elements.
	 *
	 * @param  string   $table
	 * @param  boolean  $showall
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

		$schema = static::get_table_definition($table);
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
	 * Check for any differances between the table definitions and the tables in the database.
	 * Return an array of tables along with a status and any errors
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
			$errors = Table::check($table);
			if( count($errors) > 0){
				$obj->errors 		= $errors;
				$obj->valid 		= false;
				$obj->error_type 	= 'schema_error';
			}
			$result[] = $obj;
		}
		return $result;
	}

	/**
	 * Check for any differances between the view definitions and the views in the database
	 * Return an array of views along with a status and any errors
	 *
	 * @return array
	 */
	public static function check_views()
	{
		$database 	= static::views_in_database();
		$definition = static::views_in_definition();
		$result 	= array();
		
		// Work out which views are where
		$in_both 			= array_intersect($definition, $database); // views in both the definition and database
		$just_definition 	= array_diff($definition, $database); // views only in the definition
		$just_database 		= array_diff($database, $definition); // views only in the database
		$all_views 			= array_merge($in_both, $just_definition, $just_database);
		sort($all_views);

		foreach ($all_views as $key => $view) {
			$obj 				= new \stdClass();
			$obj->name 			= $view;
			$obj->valid 		= true;
			$obj->errors 		= array();
			$obj->error_type	= '';

			if( in_array($view, $just_definition) ){
				$obj->valid 		= false;
				$obj->errors[] 		= 'This view does not exist yet.';
				$obj->error_type 	= 'missing_from_database';
			}
			elseif( in_array($view, $just_database) ){
				$obj->valid 		= false;
				$obj->errors[] 		= 'This view has not defined in the site.';
				$obj->error_type 	= 'missing_from_definition';
			}
			$result[] = $obj;
		}
		return $result;
	}

	/**
	 * Cache the Autoschema definitions.
	 *
	 * @return void
	 */
	public static function cache_definitions()
	{
		if( count(static::$tables) < 1 ){
			Log::error(__METHOD__ . ': Cache failed: not table definitions found in Autoschema::$tables.');
			return false;
		}
		Cache::forever('autoschema_definitions', array_merge(static::$tables, static::$views) );
	}

	/**
	 * Get the cached Autoschema definitions.
	 *
	 * @return array
	 */
	public static function get_definitions()
	{
		$definitions = Cache::get('autoschema_definitions');
		if( is_array($definitions) ){
			$result = new \stdClass;
			$result->views = array();
			$result->tables = array();
			foreach ($definitions as $key => $value) {
				if( get_class($value) === 'Aurer\Autoschema\View' ){
					$result->views[$key ] = $value;
				} else {
					$result->tables[$key ] = $value;
				}
			}
			return $result;
		}
		return false;
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
		$schema = static::get_table_definition($table);
		
		if( !$schema ) return false;
		
		foreach ($schema->columns as $column) {
			$definition = self::column_definition($column);
			$name = $column['name'];
			$columns[$name] = $definition;
		}
		return $columns;
	}

	/**
	 * Return the columns for a given definition.
	 *
	 * @param  string 	$table
	 * @return array
	 */
	public static function column_in_definition($table, $column_name)
	{		
		$columns = array();
		$schema = static::get_table_definition($table);
		
		if( !$schema ) return array();

		foreach ($schema->columns as $column) {
			if( $column['name'] === $column_name ){
				return $column;
			}
		}

		return array();
	}

	/**
	 * Create a new driver instance based on the default database config.
	 *
	 * @param  string  $driver
	 * @return Autoschema\Drivers\Driver
	 */
	public static function driver()
	{
		$driver = Config::get('database.default');
		
		switch ($driver) {
			case 'mysql':
				return new Drivers\MySQL;
				
			default:
				throw new \Exception("Autoschema driver {$driver} is not supported.");
		}
	}

	/**
	 * Magic Method for calling the methods on the default driver.
	 */
	public static function __callStatic($method, $parameters)
	{
		return call_user_func_array(array(static::driver(), $method), $parameters);
	}
}
