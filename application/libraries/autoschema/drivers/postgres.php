<?php namespace AutoSchema\Drivers;

use \AutoSchema\AutoSchema as AutoSchema;
use \Laravel\Database as DB;
use \Laravel\Config as Config;
use \Laravel\Log as Log;

class Postgres extends Driver {

	public static function create($table)
	{
		$schema = AutoSchema::get($table);
		if( !$schema ) return false;

		$command = "CREATE TABLE IF NOT EXISTS " . $schema->name . " (\n";
			foreach ($schema->columns as $column) {
				$command .= "\t" . static::column_definition($column) . ",\n";
			}
		$command .= "\tPRIMARY KEY (" . $schema->primary_key . ")\n";
		$command .= ");\n";
		$command2 = 'CREATE SEQUENCE ' . $table .'_'. $schema->primary_key . "_seq;\n";
		$command3 = "ALTER TABLE $table ALTER COLUMN $schema[primary_key] SET DEFAULT NEXTVAL('" . $table .'_'. $schema->primary_key . "_seq');";
		
		DB::query($command);
		DB::query($command2);
		DB::query($command3);
	}

	public static function drop($table)
	{
		$command = "DROP TABLE IF EXISTS " . $table . "\n";
		$command2 = "DROP SEQUENCE " . $table . "_id_seq\n";
		DB::query($command);
		DB::query($command2);
	}

	protected static function column_definition( $column=array() )
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
		$command = "SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH, data_type FROM information_schema.columns WHERE TABLE_SCHEMA = 'public' AND TABLE_NAME = ?";
		$result = DB::query($command, array($table) );
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

	public function update_table($table)
	{
		$table_pk 				= DB::first("SELECT pg_attribute.attname, format_type(pg_attribute.atttypid, pg_attribute.atttypmod) FROM pg_index, pg_class, pg_attribute WHERE pg_class.oid = '$table'::regclass AND indrelid = pg_class.oid AND pg_attribute.attrelid = pg_class.oid AND pg_attribute.attnum = any(pg_index.indkey) AND indisprimary")->attname;
		$columns_in_definition 	= AutoSchema::columns_in_definition($table);
		$columns_in_table 		= $this->columns_in_table($table);
		$schema 				= AutoSchema::get($table);
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
		$columns_in_table 		= $this->columns_in_table($table);
		
		// Alter table stamements
		
		foreach ($schema->columns as $column) {
			$definition = $this->column_definition($column);
			if( !array_key_exists($column['name'], $columns_in_table) ){
				$alter_statements[] = "$alter_table ADD $definition $after_previous_column;";
			} else {
				$alter_statements[]  = "$alter_table ALTER " . $column['name'] . " TYPE " . $this->translate($column, 'to_database') . ";";
			}
			if( $column['name'] == $table_pk ){
				$pk_definition = $column['name'] . str_replace($column['name'], ' TYPE', $definition);
			}
			$after_previous_column = "AFTER " . $column['name'];
		}
		foreach( $columns_in_table as $key => $column){
			if( !array_key_exists($key, $columns_in_definition) ){
				$alter_statements[] = "$alter_table DROP COLUMN $key;";
			}
		}
		
		
		if( $table_pk && !$schema->primary_key != $table_pk ){
			$alter_statements[] = "$alter_table ALTER $pk_definition;";
			$alter_statements[] = "$alter_table DROP CONSTRAINT " . $table . "_pkey;";
			$alter_statements[] = "$alter_table ADD PRIMARY KEY({$schema->primary_key});";
		}
		
		Log::AutoSchema("Updating $table");
		foreach ($alter_statements as $statement) {
			Log::AutoSchema("$statement");
			if( !DB::query($statement) ) break;
		}
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