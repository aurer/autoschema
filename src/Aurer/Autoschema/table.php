<?php namespace Aurer\Autoschema;

use \Log;

class Table
{
	public $name;
	public $primary_key;
	public $columns;

	/**
	 * Create a new Table instance
	 *
	 * @return void
	 **/
	public function __construct($name)
	{
		$this->name = $name;
		$definition = Autoschema::get_table_definition($name);
		return $definition ? $definition : $this;
	}

	/**
	 * Define a string column
	 *
	 * @param  string $name
	 * @param  int $length
	 * @return Autoschema\Table
	 */
	public function string($name, $length = 200)
	{
		return $this->column(__FUNCTION__, compact('name', 'length'));
	}

	/**
	 * Define a integer column
	 *
	 * @param  string $name
	 * @param  boolean $increment
	 * @return Autoschema\Table
	 */
	public function integer($name, $increment = false)
	{
		return $this->column(__FUNCTION__, compact('name', 'increment'));
	}

	/**
	 * Define a float column
	 *
	 * @param  string $name
	 * @return Autoschema\Table
	 */
	public function float($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	/**
	 * Define a decimal column
	 *
	 * @param  string $name
	 * @param  int $precision
	 * @param  int $scale
	 * @return Autoschema\Table
	 */
	public function decimal($name, $precision, $scale)
	{
		return $this->column(__FUNCTION__, compact('name', 'precision', 'scale'));
	}

	/**
	 * Define a text column
	 *
	 * @param  string $name
	 * @return Autoschema\Table
	 */
	public function text($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	/**
	 * Define a boolean column
	 *
	 * @param  string $name
	 * @return Autoschema\Table
	 */
	public function boolean($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	/**
	 * Define a date column
	 *
	 * @param  string $name
	 * @return Autoschema\Table
	 */
	public function date($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	/**
	 * Define a timestamp column
	 *
	 * @param  string $name
	 * @return Autoschema\Table
	 */
	public function timestamp($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	/**
	 * Define a blob column
	 *
	 * @param  string $name
	 * @return Autoschema\Table
	 */
	public function blob($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	/**
	 * Define an auto incrementing column
	 *
	 * @param  string $name
	 * @param  int $name
	 * @return Autoschema\Table
	 */
	public function increments($name)
	{
		$this->primary_key($name);
		return $this->integer($name, true);
	}

	/**
	 * Define a the tables created_at and updated_at timestamp columns
	 *
	 * @return Autoschema\Table
	 */
	public function timestamps()
	{
		$this->timestamp('created_at');
		$this->timestamp('updated_at');
		return $this;
	}

	/**
	 * Define a primary key for the table
	 *
	 * @param  string $name
	 * @return Autoschema\Table
	 */
	public function primary_key($name)
	{	
		$this->primary_key = $name;
		return $this;
	}

	/**
	 * Define a column label
	 *
	 * @param  string $label
	 * @return Autoschema\Table
	 */
	public function label($label)
	{	
		$col = array_pop($this->columns);
		$col[__FUNCTION__] = $label;
		$this->columns[] = $col;
		return $this;
	}

	/**
	 * Define the validation rules for a column
	 *
	 * @param  string $rules
	 * @return Autoschema\Table
	 */
	public function rules($rules)
	{	
		$col = array_pop($this->columns);
		$col[__FUNCTION__] = $rules;
		$this->columns[] = $col;
		return $this;
	}

	/**
	 * Define an the possible values for a table
	 *
	 * @param  mixed $values
	 * @return Autoschema\Table
	 */
	public function values($values)
	{	
		$col = array_pop($this->columns);
		$col[__FUNCTION__] = $values;
		$this->columns[] = $col;
		return $this;
	}

	/**
	 * Create an extra attribute to the table definition.
	 *
	 * @param  mixed $parameters
	 * @return Definition
	 */
	public function __call($name, $args)
	{	
		if( !in_array($name, array('name','type','length', 'label') ) ){
			$col = array_pop($this->columns);
			$col[$name] = $args[0];
			$this->columns[] = $col;
		}
		return $this;
	}

	/**
	 * Create a new fluent column instance.
	 *
	 * @param  string  $type
	 * @param  array   $parameters
	 * @return Table
	 */
	protected function column($type, $parameters = array())
	{
		$parameters = array_merge(compact('type'), $parameters);

		// Add a default label
		$parameters['label'] = ucfirst( str_replace('_', ' ', $parameters['name']) );
		
		$parameters['default'] = null;

		// Default integers to 0
		if( $type === 'integer' ){
			$parameters['default'] = 0;
		}

		// Default timestamps to the current date
		if( $type === 'timestamp' ){
			$parameters['default'] = date('Y-m-d H:i:s');
		}
		
		$this->columns[] = $parameters;

		return $this;
	}

	/**
	 * Get the differences between a table definition and the database
	 *
	 * @param  string  $name
	 * @return array
	 */
	public static function check($name)
	{
		$columns_in_definition 	= Autoschema::columns_in_definition($name);
		$columns_in_table 		= Autoschema::columns_in_table($name);
		$errors 				= array();
		$changes 				= self::diff_columns($columns_in_definition, $columns_in_table);

		foreach ($changes->renamed as $old => $new) {
			$errors[] = "The '$old' column will be renamed to '$new'";
		}

		foreach ($changes->altered as $name => $def) {
			$errors[] = "The '$name' column will be changed to '$def'";
		}

		foreach ($changes->added as $name => $def) {
			$errors[] = "The '$name' column will be added";
		}

		foreach ($changes->removed as $name => $def) {
			$errors[] = "The '$name' column will be removed";
		}
		
		return $errors;
	}

	/**
	 * Check a table definition against the database
	 *
	 * @param  string  $name
	 * @return array
	 */
	public static function diff_columns($definition, $table)
	{
		$definition_only 	= array_diff($definition, $table);
		$table_only 		= array_diff($table, $definition);
		$renamed 			= array();
		$altered 			= array();
		$changes			= new \stdClass;
		
		// Loop each definition difference
		foreach ($definition_only as $def_name => $def_def) {
			$def_type = str_replace("$def_name ", '', $def_def); // Remove the name to get just the type and length
			
			// Check for the first database column that matches the definition column type
			foreach ($table_only as $tab_name => $tab_def) {
				$tab_type = str_replace("$tab_name ", '', $tab_def); // Remove the name to get just the type ect.

				// If the types match we can assume it's been renamed
				if( $def_type === $tab_type ){
					$renamed[$tab_name] = $def_name;
					Log::info("Difference in column name. Definition: $def_name - Table: $tab_name");
					unset($table_only[$tab_name]);
					unset($definition_only[$def_name]);
					break;
				}
				
				// If the names match we can assume it's been altered
				if( $def_name === $tab_name ){
					$altered[$tab_name] = "$def_def";
					Log::info("Difference in column '$tab_name'. Definition: $def_def - Table: $tab_def");
					unset($table_only[$tab_name]);
					unset($definition_only[$def_name]);
					break;
				}
				
			}
		}

		$changes->renamed 	= $renamed;
		$changes->altered 	= $altered;
		$changes->added 	= $definition_only;
		$changes->removed 	= $table_only;
		return $changes;
	}

	/**
	 * Set an attribute of the table
	 *
	 * @param  string  $item
	 * @param  string  $value
	 * @return void
	 */
	public function __set($item, $value){
		$this->$item = $value;
	}

	/**
	 * Get an attribute of the table
	 *
	 * @param  string  $item
	 * @return mixed
	 */
	public function __get($item){
		if( isset($this->$item)){
			return $this->$item;
		}
	}
}
