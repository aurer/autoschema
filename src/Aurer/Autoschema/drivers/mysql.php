<?php namespace Aurer\Autoschema\Drivers;

use Aurer\Autoschema\Autoschema;
use Aurer\Autoschema\Table;
use DB;
use Config;
use Log;

class MySQL extends Driver {

	/**
	 * Create a table based on a schema definition
	 *
	 * @param  string $table
	 * @return void
	 **/
	public static function create_table($table, $return_command = false)
	{
		$schema = Autoschema::get_table_definition($table);
		if( !$schema ) return false;

		$command = "CREATE TABLE IF NOT EXISTS " . $schema->name . " (\n";
		
		foreach ($schema->columns as $column) {
			$command .= "\t" . static::column_definition($column) . ",\n";
		}
		
		if( !empty($schema->primary_key) ){
			$command .= "\tPRIMARY KEY (" . $schema->primary_key . ")\n";
		} else {
			$command = rtrim($command, ",\n") . "\n"; // Remove the previous comma
		}
		
		$command .= ");\n";

		if( $return_command ) return $command;

		return self::command($command);
	}

	/**
	 * Create a view based on a schema definition
	 *
	 * @param  string $view
	 * @return boolean
	 **/
	public static function create_view($name, $return_command = false)
	{
		$schema = Autoschema::get_view_definition($name);
		if( !$schema ) return false;

		$command = "CREATE OR REPLACE VIEW " . $schema->name . " AS " . $schema->definition . "\n";
		
		if( $return_command ) return $command;

		return self::command($command);
	}

	/**
	 * Drop a table from the database
	 *
	 * @param  string $table
	 * @return boolean
	 **/
	public static function drop_table($table)
	{	
		// Don't drop it, if it's in the definitions
		$schema = Autoschema::get_table_definition($table);
		if( $schema ) return false;

		$command = "DROP TABLE IF EXISTS " . $table . "\n";
		
		return self::command($command);
	}

	/**
	 * Drop a view from the database
	 *
	 * @param  string $view
	 * @return boolean
	 **/
	public static function drop_view($view)
	{	
		// Don't drop it, if it's in the definitions
		$schema = Autoschema::get_view_definition($view);
		if( $schema ) return false;

		$command = "DROP VIEW IF EXISTS " . $view . "\n";
		
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

	/**
	 * Retrive a column definition string
	 *
	 * @param  array $column
	 * @return boolean
	 **/
	public static function column_definition( $column=array() )
	{
		$definition = $column['name'] . ' ' . static::column_type_for_db($column);

		// Add NOT NULL if it's set
		if( isset($column['rules']) && strpos($column['rules'], 'required') !== false ){
			if( $column['type'] != 'timestamp' && ( !isset($column['increment']) || $column['increment'] != true ) ){
				$definition .= ' NOT NULL';
			}
		}

		// Add auto increment if it's set
		if( isset($column['increment']) && $column['increment'] == true ){
			$definition .= ' AUTO_INCREMENT';
		}

		return trim($definition);
	}

	/**
	 * Get an array of table names in the database
	 *
	 * @return array
	 **/
	public static function tables_in_database()
	{	
		$tables = array();
		$database = Config::get('database.connections');
		$command = "SELECT table_name, table_rows, data_length, auto_increment FROM information_schema.tables WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = ?";
		$result = self::command($command, array($database['mysql']['database']));
		foreach ($result as $table) {
			$tables[] = $table->table_name;
		}	
		return $tables;
	}

	/**
	 * Get an array of view names in the database
	 *
	 * @return array
	 **/
	public static function views_in_database()
	{	
		$views = array();
		$database = Config::get('database.connections');
		$command = "SELECT table_name, table_rows, data_length, auto_increment FROM information_schema.tables WHERE TABLE_TYPE = 'VIEW' AND TABLE_SCHEMA = ?";
		$result = self::command($command, array($database['mysql']['database']));
		foreach ($result as $view) {
			$views[] = $view->table_name;
		}	
		return $views;
	}

	/**
	 * Return the columns for a given table.
	 *
	 * @param  string 	$table
	 *
	 * @return array
	 */
	public static function columns_in_table($table)
	{
		$columns = array();
		$database = Config::get('database.connections');
		$command = "SELECT character_maximum_length, column_key, column_name, data_type, is_nullable, numeric_precision, numeric_scale FROM information_schema.columns WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
		$result = self::command($command, array($database['mysql']['database'], $table) );
		foreach ($result as $column) {

			$definition['name']		 = $column->column_name;
			$definition['type']		 = self::column_type_for_definition($column->data_type);
			$definition['length']	 = ($column->character_maximum_length) ? $column->character_maximum_length : null;
			$definition['precision'] = $column->data_type == 'decimal' ? $column->numeric_precision : null;
			$definition['scale']	 = $column->data_type == 'decimal' ? $column->numeric_scale : null;
			$definition['increment'] = ($column->column_key == 'PRI') ? true : false;
			
			// Add rules based on unique key and is_nullable settings
			$rules = array();
			if( $column->is_nullable == 'NO' ){
				$rules[] = 'required';
			}
			if( $column->column_key == 'UNI' ){
				$rules[] = 'unique';
			}
			$definition['rules'] = implode('|', $rules);

			if( $definition['type'] == 'text' || $definition['type'] == 'blob' ){
				$definition['length'] = null;
			}

			$columns[$column->column_name] = self::column_definition($definition);
		}
		return $columns;
	}

	/**
	 * Update a database table
	 *
	 * @param string $table
	 *
	 */
	public static function update_table($table)
	{
		// Get the defined and database columns so we can work out what to add, alter and drop.
		$columns_in_definition 	= Autoschema::columns_in_definition($table);
		$columns_in_table 		= self::columns_in_table($table);
		$schema 				= Autoschema::get_table_definition($table);

		// Get the table differences
		$diff = Table::diff_columns($columns_in_definition, $columns_in_table);

		$alter_table = "ALTER TABLE $table";
		
		// Rename columns
		foreach ($diff->renamed as $key => $value) {
			$commands[] = "$alter_table CHANGE {$key} {$columns_in_definition[$value]}";
		}

		// Modify column definitions
		foreach ($diff->altered as $key => $value) {
			$commands[] = "$alter_table MODIFY {$columns_in_definition[$key]}";
		}

		// Add columns
		foreach ($diff->added as $key => $value) {
			$commands[] = "$alter_table ADD {$columns_in_definition[$key]}";
		}

		// Drop columns
		foreach ($diff->removed as $key => $value) {
			$commands[] = "$alter_table DROP $key";
		}

		// Nothings changed so return
		if( empty($commands) ){
			return;
		}

		// Run all the commands
		foreach ($commands as $command) {
			$result = self::command($command);
			static::reload_views_dependant_on($table);
			if( !$result ) break;
		}
	}

	/**
	 * Drop any views dependant on specified table
	 *
	 * @var string $table
	 **/
	protected static function drop_views_dependant_on($table)
	{
		foreach (static::get_dependant_views_for($table) as $view) {
			static::force_drop_view($view);
		}
	}

	/**
	 * Create any views dependant on specified table
	 *
	 * @var string $table
	 **/
	protected static function create_views_dependant_on($table)
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
	protected static function reload_views_dependant_on($table)
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
		foreach (Autoschema::get_definitions()->views as $view) {
			if( in_array($table, $view->dependant_tables) ){
				$views[] = $view->name;
			}
		}
		return $views;
	}

	/**
	 * Drop and re-create a view
	 *
	 * @param string $view
	 */
	public static function update_view($view)
	{
		self::command("DROP VIEW IF EXISTS $view");
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
		$precision_and_scale = (isset($precision) && isset($scale) ) ? "($precision, $scale)" : "";

		$types = array(
			'blob'			=> 'BLOB',
			'boolean'		=> 'TINYINT(1)',
			'date'			=> 'DATE',
			'decimal'		=> 'DECIMAL' . $precision_and_scale,
			'float'			=> 'FLOAT' . $precision_and_scale,
			'integer'		=> 'INT' . $length,
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
			'CHAR'			=> 'string',
			'VARCHAR'		=> 'string',
			'TINYTEXT'		=> 'text',
			'TEXT'			=> 'text',
			'BLOB'			=> 'blob',
			'MEDIUMTEXT'	=> 'text',
			'MEDIUMBLOB'	=> 'text',
			'LONGTEXT'		=> 'text',
			'LONGBLOB'		=> 'blob',
			'ENUM'			=> 'varchar',
			'SET'			=> 'varchar',
			'TINYINT'		=> 'boolean',
			'SMALLINT'		=> 'integer',
			'MEDIUMINT'		=> 'integer',
			'INT'			=> 'integer',
			'BIGINT'		=> 'integer',
			'FLOAT'			=> 'float',
			'DOUBLE'		=> 'float',
			'DECIMAL'		=> 'decimal',
			'DATE'			=> 'date',
			'DATETIME'		=> 'timestamp',
			'TIMESTAMP'		=> 'timestamp',
			'TIME'			=> 'time',
			'YEAR'			=> 'timestamp',
		);
		return $types[strtoupper($key)];
	}

	/**
	 * Run a SQL command through lavavels database class and log the query
	 *
	 * @param string $command
	 * @return mixed
	 */
	protected static function command($command, $arguments = array())
	{
		$result = DB::select($command, $arguments);
		Log::info( "Command: " . $command );
		if( count($arguments) ){
			Log::info( "Bindings: " . implode(', ', $arguments) );
		}
		return $result;
	}
}
