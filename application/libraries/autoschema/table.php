<?php namespace AutoSchema;

use \AutoSchema\AutoSchema as AutoSchema;

class Table
{
	public $name;
	public $primary_key;
	public $columns;
	public $create;

	public function __construct($name)
	{
		$this->name = $name;
		$definition = AutoSchema::get_table_definition($name);
		return $definition ? $definition : $this;
	}

	public function string($name, $length = 200)
	{
		return $this->column(__FUNCTION__, compact('name', 'length'));
	}

	public function integer($name, $increment = false)
	{
		return $this->column(__FUNCTION__, compact('name', 'increment'));
	}

	public function float($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	public function decimal($name, $precision, $scale)
	{
		return $this->column(__FUNCTION__, compact('name', 'precision', 'scale'));
	}

	public function text($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	public function boolean($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	public function date($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	public function timestamp($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	public function blob($name)
	{
		return $this->column(__FUNCTION__, compact('name'));
	}

	public function increments($name)
	{
		$this->primary_key($name);
		return $this->integer($name, true);
	}

	public function timestamps()
	{
		$this->timestamp('created_at');
		$this->timestamp('updated_at');
	}

	public function primary_key($arg)
	{	
		$this->primary_key = $arg;
		return $this;
	}

	public function label($arg)
	{	
		$col = array_pop($this->columns);
		$col[__FUNCTION__] = $arg;
		$this->columns[] = $col;
		return $this;
	}

	public function rules($arg)
	{	
		$col = array_pop($this->columns);
		$col[__FUNCTION__] = $arg;
		$this->columns[] = $col;
		return $this;
	}

	public function values($arg)
	{	
		$col = array_pop($this->columns);
		$col[__FUNCTION__] = $arg;
		$this->columns[] = $col;
		return $this;
	}

	/**
	 * Create an extra attribute to the table definition.
	 *
	 * @param  mixed   $parameters
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
	 * @return Fluent
	 */
	protected function column($type, $parameters = array())
	{
		$parameters = array_merge(compact('type'), $parameters);

		$parameters['label'] = ucfirst( str_replace('_', ' ', $parameters['name']) );

		$this->columns[] = $parameters;

		return $this;
	}

	public static function check($name)
	{
		$columns_in_definition 	= AutoSchema::columns_in_definition($name);
		$columns_in_table 		= AutoSchema::columns_in_table($name);
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
				$tab_type = str_replace("$tab_name ", '', $tab_def); // Remove the name to get just the type and length
				
				// Type match so store the column name and remove from the definition and table arrays
				if( $def_type === $tab_type ){
					$renamed[$tab_name] = $def_name;
					unset($table_only[$tab_name]);
					unset($definition_only[$def_name]);
					break;
				}

				// Check for type changes e.g name is the same but type or length has changed
				if( $def_name === $tab_name ){
					$altered[$tab_name] = $def_def;
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

	public function __set($item, $value){
		return $this->$item = $value;
	}

	public function __get($item){
		if( isset($this->$item)){
			return $this->$item;
		}
	}
}
