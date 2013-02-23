<?php namespace AutoSchema\Drivers;

use \AutoSchema\AutoSchema;
use \AutoSchema\Table;
use \Laravel\Database as DB;
use \Laravel\Config;
use \Laravel\Log;

class Postgres extends Driver {

	public static function create_table($table)
	{
		$schema = AutoSchema::get_table_definition($table);
		if( !$schema ) return false;

		$command = "CREATE TABLE IF NOT EXISTS " . $schema->name . " (";
			foreach ($schema->columns as $column) {
				$command .= static::column_definition($column) . ",";
			}
		$command .= "PRIMARY KEY (" . $schema->primary_key . ")";
		$command .= ");";
		$command2 = 'CREATE SEQUENCE ' . $table .'_'. $schema->primary_key . "_seq;";
		$command3 = "ALTER TABLE $table ALTER COLUMN $schema->primary_key SET DEFAULT NEXTVAL('" . $table .'_'. $schema->primary_key . "_seq');";
		
		self::command($command);
		self::command($command2);
		self::command($command3);
	}

	public static function create_view($name)
	{
		$schema = AutoSchema::get_view_definition($name);
		if( !$schema ) return false;

		$command = "CREATE OR REPLACE VIEW " . $schema->name . " AS " . $schema->definition . "\n";
		
		return self::command($command);
	}

	public static function drop_table($table)
	{
		$command = "DROP TABLE IF EXISTS " . $table . "\n";
		$command2 = "DROP SEQUENCE " . $table . "_id_seq\n";
		self::command($command);
		self::command($command2);
	}

	public static function drop_view($view)
	{	
		// Don't drop it, if it's in the definitions
		$schema = AutoSchema::get_view_definition($view);
		if( $schema ) return false;

		$command = "DROP VIEW " . $view . "\n";
		
		return self::command($command);
	}

	/**
	 *
	 * Called only within this class this method does not make sure the view is not defined before dropping it
	 *
	 * @param string $view
	 *
	 */
	protected static function force_drop_view($view)
	{
		$command = "DROP VIEW " . $view . "\n";
		return self::command($command);	
	}

	public static function column_definition( $column=array() )
	{
		$definition = $column['name'] . ' ' . static::column_type_for_db($column);

		return trim($definition);
	}

	public static function tables_in_database()
	{	
		$tables = array();
		$database = Config::get('database.connections');
		$command = "SELECT * FROM information_schema.tables WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = 'public'";
		$result = self::command($command);
		foreach ($result as $table) {
			$tables[] = $table->table_name;
		}	
		return $tables;
	}

	public static function views_in_database()
	{	
		$views = array();
		$database = Config::get('database.connections');
		$command = "SELECT * FROM information_schema.tables WHERE TABLE_TYPE = 'VIEW' AND TABLE_SCHEMA = 'public'";
		$result = self::command($command);
		foreach ($result as $view) {
			$views[] = $view->table_name;
		}	
		return $views;
	}

	/**
	 * Return the columns for a given table.
	 *
	 * @param  string 	$table
	 * @return array
	 */
	public static function columns_in_table($table)
	{
		$columns = array();
		$database = Config::get('database.connections');
		$command = "SELECT * FROM information_schema.columns WHERE TABLE_SCHEMA = 'public' AND TABLE_NAME = ?";
		$result = self::command($command, array($table) );
		foreach ($result as $column) {

			$definition['name']		 = $column->column_name;
			$definition['type']		 = self::column_type_for_definition($column->data_type);
			$definition['length']	 = ($column->character_maximum_length) ? $column->character_maximum_length : null;
			$definition['precision'] = ($column->data_type == 'decimal' || $column->data_type == 'numeric') ? $column->numeric_precision : null;
			$definition['scale']	 = ($column->data_type == 'decimal' || $column->data_type == 'numeric') ? $column->numeric_scale : null;

			if( $definition['type'] == 'text' ){
				$definition['length'] = null;
			}

			$columns[$column->column_name] = self::column_definition($definition);
		}
		return $columns;
	}

	/**
	 * Update a table based on definition by performing the necessary UPDATE TABLE commands
	 *
	 * @var string $table
	 **/
	public static function update_table($table)
	{
		// Get the defined and database columns so we can work out what to add, alter and drop.
		$columns_in_definition 	= AutoSchema::columns_in_definition($table);
		$columns_in_table 		= self::columns_in_table($table);
		$schema 				= AutoSchema::get_table_definition($table);

		// Get the table differences
		$diff = Table::diff_columns($columns_in_definition, $columns_in_table);

		$alter_table 			= "ALTER TABLE $table";
		foreach ($diff->renamed as $key => $value) {
			$commands[] = "$alter_table RENAME {$key} TO {$value}";
		}

		foreach ($diff->altered as $key => $value) {
			$type = str_replace("$key ", '', $columns_in_definition[$key]);
			// run these commands now as they must be run in the correct order
			static::drop_views_dependant_on($table);
			self::command("$alter_table ALTER COLUMN {$key} TYPE $type USING CAST($key AS $type)");
			static::create_views_dependant_on($table);
		}

		foreach ($diff->added as $key => $value) {
			$commands[] = "$alter_table ADD {$columns_in_definition[$key]}";
		}

		foreach ($diff->removed as $key => $value) {
			$commands[] = "$alter_table DROP $key";
		}

		if( empty($commands) ){
			return;
		}

		foreach ($commands as $command) {
			if( !self::command($command) ) break;
		}
	}

	/**
	 * Drop any views dependant on specified table
	 *
	 * @var string $table
	 **/
	public static function drop_views_dependant_on($table)
	{
		foreach (static::get_dependant_views_for($table) as $view) {
			echo $view;
			static::force_drop_view($view);
		}
	}

	/**
	 * Create any views dependant on specified table
	 *
	 * @var string $table
	 **/
	public static function create_views_dependant_on($table)
	{
		foreach (static::get_dependant_views_for($table) as $view) {
			static::create_view($view);
		}
	}

	/**
	 * Drop then create any views dependant on specified table
	 *
	 * @var string $table
	 **/
	public static function reload_views_dependant_on($table)
	{
		foreach (static::get_dependant_views_for($table) as $view) {
			static::update_view($view);
		}
	}

	/**
	 * Get an array of all views dependant on specified table
	 *
	 * @var string $table
	 * @return array
	 **/
	protected static function get_dependant_views_for($table)
	{
		$views = array();
		foreach (AutoSchema::get_definitions()->views as $view) {
			if( in_array($table, $view->dependant_tables) ){
				$views[] = $view->name;
			}
		}
		return $views;
	}

	/**
	 * Drop then create the specified view
	 *
	 * @var string $view
	 * @return boolean
	 **/
	public static function update_view($view)
	{
		self::drop_view($command);
		return self::create_view($view);
	}

	/**
	 * Translate a definition data_type to a database version
	 *
	 * @param  array 	$column
	 * @return string
	 */
	protected static function column_type_for_db($column){
		extract($column);

		$length = isset($length) ? "($length)" : "";
		$precision_and_scale = (isset($precision) && isset($scale) ) ? "($precision,$scale)" : "";

		$types = array(
			'blob'			=> 'BYTEA',
			'boolean'		=> 'BOOLEAN',
			'date'			=> 'DATE',
			'decimal'		=> 'DECIMAL' . $precision_and_scale,
			'float'			=> 'FLOAT8',
			'integer'		=> 'INTEGER',
			'string'		=> 'VARCHAR' . $length,
			'text'			=> 'TEXT',
			'time'			=> 'TIME',
			'timestamp'		=> 'TIMESTAMP',
		);
		return $types[$type];
	}

	/**
	 * Translate a database data_type to a simple definition version
	 *
	 * @param  string 	$key
	 * @return string
	 */
	protected static function column_type_for_definition($key){
		$types = array(
			'bit varying' 					=> 'string',
			'bit' 					 		=> 'string',
			'bytea' 					 	=> 'blob',
			'bool' 					 		=> 'boolean',
			'boolean' 					 	=> 'boolean',
			'date' 					 		=> 'date',
			'decimal' 						=> 'decimal',
			'double precision' 				=> 'float',
			'float4' 					 	=> 'float',
			'float8' 					 	=> 'float',
			'money' 					 	=> 'float',
			'real' 					 		=> 'float',
			'bigint' 					 	=> 'integer',
			'bigserial' 					=> 'integer',
			'int' 					 		=> 'integer',
			'int2' 					 		=> 'integer',
			'int4' 					 		=> 'integer',
			'int8' 					 		=> 'integer',
			'integer' 					 	=> 'integer',
			'interval' 						=> 'integer',
			'numeric' 						=> 'decimal',
			'serial' 					 	=> 'integer',
			'serial4' 					 	=> 'integer',
			'serial8' 					 	=> 'integer',
			'smallint' 					 	=> 'integer',
			'box' 					 		=> 'string',
			'char' 					 		=> 'string',
			'character varying' 			=> 'string',
			'character' 					=> 'string',
			'cidr' 					 		=> 'string',
			'circle' 					 	=> 'string',
			'inet' 					 		=> 'string',
			'line' 					 		=> 'string',
			'lseg' 					 		=> 'string',
			'macaddr' 					 	=> 'string',
			'path' 					 		=> 'string',
			'point' 					 	=> 'string',
			'polygon' 					 	=> 'string',
			'varchar' 						=> 'string',
			'text' 					 		=> 'text',
			'time with time zone' 			=> 'time',
			'time without time zone' 		=> 'time',
			'time' 					 		=> 'time',
			'timez' 					 	=> 'time',
			'timestamp with time zone' 		=> 'timestamp',
			'timestamp without time zone'	=> 'timestamp',
			'timestamp' 					=> 'timestamp',
			'timestampz' 					=> 'timestamp',
			'varbit' 					 	=> 'string',
		);
		return $types[$key];
	}

	/**
	 * Run a SQL command through lavavels database class and log the query
	 *
	 * @param string $command
	 *
	 * @return mixed
	 */
	protected static function command($command, $arguments = null)
	{
		$result = DB::query($command, $arguments);
		$profile = DB::profile();
		Log::AutoSchema( "Command: " . $command );
		if( count($arguments) ){
			Log::AutoSchema( "Bindings: " . implode(', ', $arguments) );
		}
		return $result;
	}
}