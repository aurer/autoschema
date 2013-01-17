<?php namespace AutoSchema;

use \AutoSchema\AutoSchema as AutoSchema;

class Table
{
	protected $definition;
	protected $errors = [];

	function __construct($definition)
	{
		$this->definition = $definition;
		return $this;
	}

	public function check()
	{
		$columns_in_definition = AutoSchema::columns_in_definition($this->definition['name']);
		$columns_in_table = AutoSchema::columns_in_table($this->definition['name']);
		foreach ($columns_in_definition as $key => $value) {
			// Column isn't in database
			if( !array_key_exists($key, $columns_in_table) ){
				$this->errors[] = "The '$key' column has been added.";
			}
			// Column definition differs
			elseif( $value !== $columns_in_table[$key] ){
				$def_str = str_replace($key." ", '', $value);
				$db_str  = str_replace($key." ", '', $columns_in_table[$key]);
				$this->errors[] = "The '$key' column has changed: schema($def_str), database($db_str)";
			}
		}
		foreach ($columns_in_table as $key => $value) {
			// Column isn't in definition
			if( !array_key_exists($key, $columns_in_definition) ){
				$this->errors[] = "The '$key' column has been removed.";
			}
		}
		return $this->errors;
	}
}