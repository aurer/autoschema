<?php namespace AutoSchema\Drivers;

use \AutoSchema\AutoSchema;
use \AutoSchema\Table;
use \Laravel\Database as DB;
use \Laravel\Config;
use \Laravel\Log;

class Postgres implements Driver {

	public static function create_table($table)
	{
		$schema = AutoSchema::get_table_definition($table);
		if( !$schema ) return false;

		$command = "CREATE TABLE IF NOT EXISTS " . $schema->name . " (\n";
			foreach ($schema->columns as $column) {
				$command .= "\t" . static::column_definition($column) . ",\n";
			}
		$command .= "\tPRIMARY KEY (" . $schema->primary_key . ")\n";
		$command .= ");\n";
		$command2 = 'CREATE SEQUENCE ' . $table .'_'. $schema->primary_key . "_seq;\n";
		$command3 = "ALTER TABLE $table ALTER COLUMN $schema->primary_key SET DEFAULT NEXTVAL('" . $table .'_'. $schema->primary_key . "_seq');";
		
		DB::query($command);
		DB::query($command2);
		DB::query($command3);
	}

	public static function create_view($name)
	{
		$schema = AutoSchema::get_view_definition($name);
		if( !$schema ) return false;

		$command = "CREATE OR REPLACE VIEW " . $schema->name . " AS " . $schema->definition . "\n";
		Log::AutoSchema($command);
		echo DB::query($command);
	}

	public static function drop_table($table)
	{
		$command = "DROP TABLE IF EXISTS " . $table . "\n";
		$command2 = "DROP SEQUENCE " . $table . "_id_seq\n";
		DB::query($command);
		DB::query($command2);
	}

	public static function drop_view($view)
	{	
		// Don't drop it, if it's in the definitions
		$schema = AutoSchema::get_view_definition($view);
		if( $schema ) return false;

		$command = "DROP VIEW " . $view . "\n";
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
			'boolean'	=> 'BOOLEAN',
			'date'		=> 'DATE',
			'timestamp'	=> 'TIMESTAMP',
			'blob'		=> 'BLOB',
			'default'	=> 'VARCHAR',
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
		/*if( isset($column['increment']) && $column['increment'] == true ){
			$definition .= ' SERIAL';
		}*/

		return trim($definition);
	}

	public static function tables_in_database()
	{	
		$tables = array();
		$database = Config::get('database.connections');
		$command = "SELECT * FROM information_schema.tables WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = 'public'";
		$result = DB::query($command);
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
		$result = DB::query($command);
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
			'character varying' => 'string',
			'integer' 			=> 'integer',
			'float' 			=> 'float',
			'decimal' 			=> 'decimal',
			'text' 				=> 'text',
			'boolean' 			=> 'boolean',
			'date' 				=> 'date',
			'timestamp' 		=> 'timestamp',
			'timestamp without time zone' 		=> 'timestamp',
			'datetime' 			=> 'timestamp',
			'blob' 				=> 'blob',
		);

		$database = Config::get('database.connections');
		$command = "SELECT * FROM information_schema.columns WHERE TABLE_SCHEMA = 'public' AND TABLE_NAME = ?";
		$result = DB::query($command, array($table) );
		foreach ($result as $column) {

			$definition['name']		 = $column->column_name;
			$definition['type']		 = $translate[$column->data_type];
			$definition['length']	 = ($column->character_maximum_length) ? $column->character_maximum_length : null;
			$definition['precision'] = $column->data_type == 'decimal' ? $column->numeric_precision : null;
			$definition['scale']	 = $column->data_type == 'decimal' ? $column->numeric_scale : null;

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
		//$table_pk 				= DB::first("SHOW INDEX FROM $table")->column_name;

		// Get the table differences
		$diff = Table::diff_columns($columns_in_definition, $columns_in_table);

		$alter_table 			= "ALTER TABLE $table";
		foreach ($diff->renamed as $key => $value) {
			$commands[] = "$alter_table RENAME {$key} TO {$value}";
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

	/**
	 * Return the columns for a given table.
	 *
	 * @param  string 	$table
	 * @return array
	 */
	public static function translate($column, $from)
	{
		$length = isset($column['length']) ? '(' . $column['length'] . ')' : '';
		$translate['to_definition'] = array(
			'character varying' => 'string',
			'integer' 			=> 'integer',
			'float' 			=> 'float',
			'decimal' 			=> 'decimal',
			'text' 				=> 'text',
			'boolean' 			=> 'boolean',
			'date' 				=> 'date',
			'timestamp' 		=> 'timestamp',
			'datetime' 			=> 'timestamp',
			'blob' 				=> 'blob',
			'default'			=> 'string',
		);

		$translate['to_database'] = array(
			'string'	=> 'VARCHAR' . $length,
			'integer'	=> 'INT' . $length,
			'float'		=> 'FLOAT',
			'decimal'	=> 'DECIMAL',
			'text'		=> 'TEXT' . $length,
			'boolean'	=> 'BOOLEAN',
			'date'		=> 'DATE',
			'timestamp'	=> 'TIMESTAMP',
			'blob'		=> 'BLOB',
			'default'	=> 'VARCHAR(200)',
		);

		if( !array_key_exists($from, $translate) ){
			return $column['type'];
		}
		if( !array_key_exists($column['type'], $translate[$from]) ){
			return $translate[$from]['default'];
		}
		return $translate[$from][$column['type']];
	}
}