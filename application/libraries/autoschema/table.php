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
		$errors = array();
		
		foreach ($columns_in_definition as $key => $value) {
			// Column isn't in database
			if( !array_key_exists($key, $columns_in_table) ){
				$errors[] = "The '$key' column has been added.";
			}
			// Column definition differs
			elseif( $value !== $columns_in_table[$key] ){
				$def_str = str_replace($key." ", '', $value);
				$db_str  = str_replace($key." ", '', $columns_in_table[$key]);
				$errors[] = "The '$key' column has changed: schema($def_str), database($db_str)";
			}
		}
		foreach ($columns_in_table as $key => $value) {
			// Column isn't in definition
			if( !array_key_exists($key, $columns_in_definition) ){
				$errors[] = "The '$key' column has been removed.";
			}
		}
		return $errors;
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
