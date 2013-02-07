<?php namespace AutoSchema\Drivers;

use \AutoSchema\AutoSchema;
use \AutoSchema\Table;
use \Laravel\Database as DB;
use \Laravel\Config;
use \Laravel\Log;

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
			'varchar' 	 => 'string',
			'int' 		 => 'integer',
			'float' 	 => 'float',
			'decimal' 	 => 'decimal',
			'text' 		 => 'text',
			'tinyint' 	 => 'boolean',
			'date' 		 => 'date',
			'timestamp'  => 'timestamp',
			'blob' 		 => 'blob',
		);
		$database = Config::get('database.connections');
		$command = "SELECT * FROM information_schema.columns WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
		$result = DB::query($command, array($database['mysql']['database'], $table) );
		foreach ($result as $column) {

			$definition['name']		 = $column->column_name;
			$definition['type']		 = $translate[$column->data_type];
			$definition['length']	 = ($column->character_maximum_length) ? $column->character_maximum_length : null;
			$definition['precision'] = $column->data_type == 'decimal' ? $column->numeric_precision : null;
			$definition['scale']	 = $column->data_type == 'decimal' ? $column->numeric_scale : null;
			$definition['increment'] = ($column->column_key == 'PRI') ? true : false;

			if( $definition['type'] == 'text' ){
				$definition['length'] = null;
			}

			$columns[$column->column_name] = self::column_definition($definition);
		}
		return $columns;
	}

	public static function update_table($table)
	{
		// Get the defined and database columns so we can work out what to add, alter and drop.
		$columns_in_definition 	= AutoSchema::columns_in_definition($table);
		$columns_in_table 		= self::columns_in_table($table);
		$schema 				= AutoSchema::get_table_definition($table);
		$table_pk 				= DB::first("SHOW INDEX FROM $table")->column_name;

		// Get the table differences
		$diff = Table::diff_columns($columns_in_definition, $columns_in_table);

		$alter_table 			= "ALTER TABLE $table";
		foreach ($diff->renamed as $key => $value) {
			$commands[] = "$alter_table CHANGE {$key} {$columns_in_definition[$value]}";
		}

		foreach ($diff->altered as $key => $value) {
			$commands[] = "$alter_table MODIFY {$columns_in_definition[$key]}";
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

		Log::AutoSchema("Updating $table");
		foreach ($commands as $command) {
			Log::AutoSchema("$command");
			if( !DB::query($command) ) break;
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
