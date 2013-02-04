<?php namespace AutoSchema\Drivers;

use \AutoSchema\AutoSchema;
use \AutoSchema\Table;
use \Laravel\Database as DB;
use \Laravel\Config;
use \Laravel\Log;

class SQLite implements Driver{

	public static function create_table($table)
	{
		$schema = AutoSchema::get_table_definition($table);
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
		Log::AutoSchema($command);
		return DB::query($command);
	}

	public static function create_view($name)
	{
		$schema = AutoSchema::get_view_definition($name);
		if( !$schema ) return false;

		$command = "CREATE VIEW " . $schema->name . " AS " . $schema->definition . "\n";
		Log::AutoSchema($command);
		return DB::query($command);
	}

	public static function drop_table($table)
	{	
		// Don't drop it, if it's in the definitions
		$schema = AutoSchema::get_table_definition($table);
		if( $schema ) return false;

		$command = "DROP TABLE IF EXISTS " . $table . "\n";
		Log::AutoSchema($command);
		return DB::query($command);
	}

	public static function drop_view($view)
	{	
		// Don't drop it, if it's in the definitions
		$schema = AutoSchema::get_view_definition($view);
		if( $schema ) return false;

		$command = "DROP VIEW IF EXISTS " . $view . "\n";
		Log::AutoSchema($command);
		return DB::query($command);
	}

	public static function column_definition( $column=array() )
	{
		$definition = $column['name'] . " ";
		$types = array(
			'string'	=> 'VARCHAR',
			'integer'	=> 'INT',
			'float'		=> 'FLOAT',
			'decimal'	=> 'DECIMAL',
			'text'		=> 'TEXT',
			'boolean'	=> 'TINYINT(1)',
			'date'		=> 'DATE',
			'timestamp'	=> 'TIMESTAMP',
			'blob'		=> 'BLOB',
			'default'	=> 'VARCHAR(200)',
		);

		// Set the type
		if( array_key_exists($column['type'], $types) ){
			$definition .= $types[$column['type']];
		} else {
			$definition .= $types['default'];
		}

		// Add length if it's set
		if( isset($column['length']) ){
			$definition .= '(' . $column['length'] . ')';
		}

		// Add precision and scale if they're present
		if( isset($column['precision']) && isset($column['scale']) ){
			$definition .= '(' . $column['precision'] . ',' . $column['scale'] . ')';
		}

		// Add auto increment if it's set
		if( isset($column['increment']) && $column['increment'] == true ){
			$definition .= ' AUTO_INCREMENT';
		}

		return trim($definition);
	}

	public static function tables_in_database()
	{	
		$config = Config::get('database.connections');
		$tables = array();
		$database_file = path('storage').'database'.DS.$config['sqlite']['database'].'.sqlite';

		if( !is_file($database_file) ) return array();

		if( file_get_contents($database_file) == '' ) return array();

		$command = "SELECT * FROM sqlite_master WHERE type = 'table'";
		$result = DB::query($command);
		foreach ($result as $table) {
			$tables[] = $table->name;
		}	
		return $tables;
	}

	public static function views_in_database()
	{	
		$config = Config::get('database.connections');
		$views = array();
		$database_file = path('storage').'database'.DS.$config['sqlite']['database'].'.sqlite';

		if( !is_file($database_file) ) return array();

		if( file_get_contents($database_file) == '' ) return array();

		$command = "SELECT * FROM sqlite_master WHERE type = 'view'";
		$result = DB::query($command);
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
		$translate = array(
			'varchar' 	=> 'string',
			'int' 		=> 'integer',
			'float' 	=> 'float',
			'decimal' 	=> 'decimal',
			'text' 		=> 'text',
			'tinyint' 	=> 'boolean',
			'date' 		=> 'date',
			'timestamp' => 'timestamp',
			'datetime' 	=> 'timestamp',
			'blob' 		=> 'blob',
		);
		$database = Config::get('database.connections');
		$result = self::raw_query("PRAGMA table_info($table);");
		foreach ($result as $column) {
			$name = $column->name;
			$type = $translate[ preg_match('/^[\w]+/', $column->type, $type_match) ? strtolower($type_match[0]) : 'varchar' ];
			$length = preg_match('/[0-9]+/', $column->type, $len_matches) ? $len_matches[0] : '';
			
			// Remove length for these types as they don't have length defined in the schema.
			if( in_array($type, array('text', 'boolean')) ){
				$length = '';
			}

			$columns[$name] = trim("$name $type $length");
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
		$result = DB::query($command);
		Log::AutoSchema($command);
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
	 * Translate between the definition and database column types.
	 *
	 * @param  string 	$table
	 * @param  string 	$to
	 * @return array
	 */
	private static function translate($column, $to='database')
	{
		$length = isset($column['length']) ? '(' . $column['length'] . ')' : '';
		
		$translate['definition'] = array(
			'VARCHAR' 		=> 'string',
			'INT' 			=> 'integer',
			'FLOAT' 		=> 'float',
			'DECIMAL' 		=> 'decimal',
			'TEXT' 			=> 'text',
			'BOOLEAN' 		=> 'boolean',
			'DATE' 			=> 'date',
			'TIMESTAMP' 	=> 'timestamp',
			'BLOB' 			=> 'blob',
		);

		$translate['database'] = array(
			'string'	=> 'VARCHAR' . $length,
			'integer'	=> 'INT' . $length,
			'float'		=> 'FLOAT',
			'decimal'	=> 'DECIMAL',
			'text'		=> 'TEXT' . $length,
			'boolean'	=> 'BOOLEAN',
			'date'		=> 'DATE',
			'timestamp'	=> 'TIMESTAMP',
			'blob'		=> 'BLOB',
		);

		if( !array_key_exists($column['type'], $translate[$to]) ){
			return $translate[$to]['default'];
		}
		return $translate[$to][$column['type']];
	}

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
}
