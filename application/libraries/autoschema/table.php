<?php namespace AutoSchema;

use \AutoSchema\AutoSchema as AutoSchema;

class Table
{
	function __construct($definition)
	{
		foreach ($definition as $key => $value){
			$this->$key = $value;
		}
		$this->errors = array();
		return $this;
	}

	public function check()
	{
		$columns_in_definition 	= AutoSchema::columns_in_definition($this->name);
		$columns_in_table 		= AutoSchema::columns_in_table($this->name);
		
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

	public function __set($item, $value){
		return $this->$item = $value;
	}

	public function __get($item){
		if( isset($this->$item)){
			return $this->$item;
		}
	}	
}
