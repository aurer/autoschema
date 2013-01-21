<?php namespace AutoSchema;

use \AutoSchema\AutoSchema as AutoSchema;

class View
{
	function __construct($name)
	{
		$this->name = $name;
		//$this->errors = array();
		return $this;
	}

	function definition($definition)
	{	
		$this->definition = $definition;
		$this->get_dependant_tables();
		return $this;
	}

	public function depends_on($tables)
	{
		if( is_array($tables) ){
			$this->dependant_tables = $tables;
		} else {
			$this->dependant_tables = func_get_args();	
		}
		return $this;
	}

	protected function get_dependant_tables()
	{
		$tables = AutoSchema::tables_in_definition();
		$definition = $this->definition;
		$matches = array();
		foreach ($tables as $key => $val) {
			if( strpos($definition, " $val ") ){
				$matches[] = $tables[$key];
			}
		}
		$this->dependant_tables = $matches;
		return $this;
	}
}