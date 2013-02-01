<?php namespace AutoSchema\Drivers;

use \AutoSchema\AutoSchema as AutoSchema;
use \Laravel\Database as DB;
use \Laravel\Config as Config;
use \Laravel\Log as Log;

class MySQL implements Driver {

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

		$command = "CREATE OR REPLACE VIEW " . $schema->name . " AS " . $schema->definition . "\n";
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
		$tables = array();
		$database = Config::get('database.connections');
		$command = "SELECT table_name, table_rows, data_length, auto_increment FROM information_schema.tables WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = ?";
		$result = DB::query($command, array($database['mysql']['database']));
		foreach ($result as $table) {
			$tables[] = $table->table_name;
		}	
		return $tables;
	}

	public static function views_in_database()
	{	
		$views = array();
		$database = Config::get('database.connections');
		$command = "SELECT table_name, table_rows, data_length, auto_increment FROM information_schema.tables WHERE TABLE_TYPE = 'VIEW' AND TABLE_SCHEMA = ?";
		$result = DB::query($command, array($database['mysql']['database']));
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
		$command = "SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH, data_type FROM information_schema.columns WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
		$result = DB::query($command, array($database['mysql']['database'], $table) );
		foreach ($result as $column) {
			$name = $column->column_name;
			$type = $translate[$column->data_type];
			$length = ($column->character_maximum_length) ? $column->character_maximum_length : '';
			
			// Remove length for these types as they don't have length defined in the schema.
			if( in_array($type, array('text')) ){
				$length = '';
			}

			$columns[$name] = trim("$name $type $length");
		}
		return $columns;
	}

	public static function update_table($table)
	{
		$table_pk 				= DB::first("SHOW INDEX FROM $table")->column_name;
		$columns_in_definition 	= AutoSchema::columns_in_definition($table);
		$columns_in_table 		= self::columns_in_table($table);
		$schema 				= AutoSchema::get_table_definition($table);
		$alter_table 			= "ALTER TABLE $table";
		$pk_definition			= "";
		$alter_statements 		= array();
		$after_previous_column  = "";

		if( !$schema ) {
			Log::notice("AutoSchema: the '$table' table is not defined");
			return false;
		}

		// Get the defined and database columns so we can work out what to add, alter and drop.
		$columns_in_definition 	= AutoSchema::columns_in_definition($table);
		$columns_in_table 		= self::columns_in_table($table);
		
		// Alter table stamements
		
		foreach ($schema->columns as $column) {
			$definition = self::column_definition($column);
			if( !array_key_exists($column['name'], $columns_in_table) ){
				$alter_statements[] = "$alter_table ADD $definition $after_previous_column;";
			} else {
				$alter_statements[]  = "$alter_table MODIFY $definition $after_previous_column;";
			}
			if( $column['name'] == $table_pk ){
				$pk_definition = str_replace('AUTO_INCREMENT', '', $definition);
			}
			$after_previous_column = "AFTER " . $column['name'];
		}
		foreach( $columns_in_table as $key => $column){
			if( !array_key_exists($key, $columns_in_definition) ){
				$alter_statements[] = "$alter_table DROP COLUMN $key;";
			}
		}
		
		
		if( $table_pk && !$schema->primary_key != $table_pk ){
			$alter_statements[] = "$alter_table MODIFY $pk_definition;";
			$alter_statements[] = "$alter_table DROP PRIMARY KEY;";
			$alter_statements[] = "$alter_table ADD PRIMARY KEY({$schema->primary_key});";
		}
		
		Log::AutoSchema("Updating $table");
		foreach ($alter_statements as $statement) {
			Log::AutoSchema("$statement");
			if( !DB::query($statement) ) break;
		}
	}

	public static function update_view($view)
	{
		$command = "DROP VIEW IF EXISTS $view";
		$result = DB::query($command);
		Log::AutoSchema($command);
		return self::create_view($view);
	}
}
