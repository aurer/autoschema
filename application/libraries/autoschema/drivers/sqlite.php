<?php namespace AutoSchema\Drivers;

use \AutoSchema\AutoSchema;
use \AutoSchema\Table;
use \Laravel\Database as DB;
use \Laravel\Config;
use \Laravel\Log;

class SQLite extends Driver{

	/**
	 * Create a table based on a schema definition
	 *
	 * @param  string $table
	 * @return void
	 **/
	public static function create_table($table)
	{
		$schema = AutoSchema::get_table_definition($table);
		if( !$schema ) return false;

		$command = "CREATE TABLE IF NOT EXISTS " . $schema->name . " (\n";
		foreach ($schema->columns as $column) {
			$command .= "\t" . static::column_definition($column) . ",\n";
		}
		
		$command = rtrim($command, ",\n") . "\n"; // Remove the previous comma
		$command .= ");\n";
		
		return self::command($command);
	}

	/**
	 * Create a view based on a schema definition
	 *
	 * @param  string $view
	 * @return boolean
	 **/
	public static function create_view($view)
	{
		$schema = AutoSchema::get_view_definition($view);
		if( !$schema ) return false;

		$command = "CREATE VIEW " . $schema->name . " AS " . $schema->definition . "\n";
		
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
		$schema = AutoSchema::get_table_definition($table);
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
		$schema = AutoSchema::get_view_definition($view);
		if( $schema ) return false;

		$command = "DROP VIEW IF EXISTS " . $view . "\n";
		
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

		// Add auto increment if it's set
		if( isset($column['increment']) && $column['increment'] == true ){
			$definition .= ' PRIMARY KEY AUTOINCREMENT NOT NULL';
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
		$config = Config::get('database.connections');
		$tables = array();
		$database_file = path('storage').'database'.DS.$config['sqlite']['database'].'.sqlite';

		if( !is_file($database_file) ) return array();

		if( file_get_contents($database_file) == '' ) return array();

		$command = "SELECT * FROM sqlite_master WHERE type = 'table'";
		$result = self::command($command);
		foreach ($result as $table) {
			if( $table->name !== 'sqlite_sequence'){
				$tables[] = $table->name;
			}
		}	
		return $tables;
	}

	/**
	 * Get an array of views in the database
	 *
	 * @param  string $view
	 * @return boolean
	 **/
	public static function views_in_database()
	{	
		$config = Config::get('database.connections');
		$views = array();
		$database_file = path('storage').'database'.DS.$config['sqlite']['database'].'.sqlite';

		if( !is_file($database_file) ) return array();

		if( file_get_contents($database_file) == '' ) return array();

		$command = "SELECT * FROM sqlite_master WHERE type = 'view'";
		$result = self::command($command);
		foreach ($result as $view) {
			$views[] = $view->name;
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
		$result = self::raw_query("PRAGMA table_info($table);");
		foreach ($result as $column) {
			
			$definition['name']		 = $column->name;
			$definition['type']		 = self::column_type_for_definition($column->type);
			$definition['length']	 = preg_match('/[0-9]+/', $column->type, $len_matches) ? $len_matches[0] : null;
			$definition['increment'] = ($column->pk == 1) ? true : false;
			
			// If the type has two numbers e.g DECIMAL(10,2) then add the precision and scale attributes
			preg_match_all('/\d+/', $column->type, $matches);
			if( count($matches[0]) === 2 ){
				$definition['precision'] = $matches[0][0];
				$definition['scale']	 = $matches[0][1];
			}

			$columns[$column->name] = self::column_definition($definition);
		}
		return $columns;
	}

	/**
	 * Update a table to match the definition by adding, removing and altering columns
	 *
	 * @return void
	 * @author 
	 **/
	public static function update_table($table)
	{
		// Get the defined and database columns so we can work out what to add, alter and drop.
		$columns_in_definition 	= AutoSchema::columns_in_definition($table);
		$columns_in_table 		= self::columns_in_table($table);

		// Get the table differences
		$diff = Table::diff_columns($columns_in_definition, $columns_in_table);

		// Remove columns that shouldn't be therr
		$insert_columns = array_diff(array_keys($columns_in_definition), array_keys($diff->added));
		$select_columns = array_diff(array_keys($columns_in_table), array_keys($diff->removed));

		self::raw_query("BEGIN TRANSACTION");
		
		// Rename the old table
		self::raw_query("ALTER TABLE {$table} RENAME TO {$table}_temp");
		
		// Create a duplicate of the old table
		self::create_table($table);

		// Copy data into the new table
		self::raw_query("INSERT INTO $table(". implode(',', $insert_columns) .") SELECT ". implode(',', $select_columns) ." FROM {$table}_temp");
		
		// Drop the old table
		self::raw_query("DROP TABLE {$table}_temp");
		
		self::raw_query("END TRANSACTION");
	}

	/**
	 * Drop and re-create a view in the definition
	 *
	 * @return void
	 * @author 
	 **/
	public static function update_view($view)
	{
		$command = "DROP VIEW IF EXISTS $view";
		$result = self::command($command);
		return self::create_view($view);
	}

	private static function get_table_pk($table)
	{
		$result = self::raw_query("PRAGMA table_info($table);");
		foreach ($result as $column) {
			if( $column->pk === 1 ){
				return $column->name;
			}
		}
		return '';
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
			'boolean'		=> 'BOOLEAN',
			'date'			=> 'DATE',
			'decimal'		=> 'DECIMAL' . $precision_and_scale,
			'float'			=> 'FLOAT' . $precision_and_scale,
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
		$sqlite_types = array(
			'INT'					=> 'integer',
			'INTEGER'				=> 'integer',
			'TINYINT'				=> 'boolean',
			'SMALLINT'				=> 'integer',
			'MEDIUMINT'				=> 'integer',
			'BIGINT'				=> 'integer',
			'UNSIGNED BIG INT'		=> 'integer',
			'INT2'					=> 'integer',
			'INT8'					=> 'integer',
			'CHARACTER'				=> 'string',
			'VARCHAR'				=> 'string',
			'VARYING CHARACTER'		=> 'string',
			'NCHAR'					=> 'string',
			'NATIVE CHARACTER'		=> 'string',
			'NVARCHAR'				=> 'string',
			'TEXT'					=> 'text',
			'CLOB'					=> 'blob',
			'BLOB'					=> 'blob',
			'REAL'					=> 'integer',
			'DOUBLE'				=> 'float',
			'DOUBLE PRECISION'		=> 'float',
			'FLOAT'					=> 'float',
			'NUMERIC'				=> 'decimal',
			'DECIMAL'				=> 'decimal',
			'BOOLEAN'				=> 'boolean',
			'DATE'					=> 'date',
			'DATETIME'				=> 'timestamp',
			'TIMESTAMP'				=> 'timestamp',
		);
		preg_match('/^\\w+/', $key, $matches);
		return $sqlite_types[$matches[0]];
	}

	/**
	 * Perform a database query that laravel can't handle
	 *
	 * @param  string 	$query
	 * @return mixed
	 */
	private static function raw_query($query)
	{
		$config = Config::get('database.connections');
		$database_file = path('storage').'database'.DS.$config['sqlite']['database'].'.sqlite';

		$db = new \PDO('sqlite:'.$database_file, null, null, $config['sqlite']);
		if ($db)
		{
	        Log::AutoSchema(__METHOD__.": ".$query);
	        $result = @$db->query($query);
	        if( $result ){
	        	return $result->fetchAll(\PDO::FETCH_CLASS);
	        }
	    } 
	    else
	    {
	        return array();
	    }
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
