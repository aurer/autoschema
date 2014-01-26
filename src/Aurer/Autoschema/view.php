<?php namespace Aurer\Autoschema;

class View
{
	/**
	 * Create a new View instance
	 *
	 * @return void
	 **/
	function __construct($name)
	{
		$this->name = $name;
		$definition = Autoschema::get_view_definition($name);
		return $definition ? $definition : $this;
	}

	/**
	 * Define a view
	 *
	 * @return View
	 **/
	function definition($definition)
	{	
		$this->definition = $definition;
		$this->get_dependant_tables();
		return $this;
	}

	/**
	 * Add dependancies to the view definition
	 *
	 * @param  array $tables
	 * @return View
	 **/
	public function depends_on($tables)
	{
		if( is_array($tables) ){
			$this->dependant_tables = $tables;
		} else {
			$this->dependant_tables = func_get_args();	
		}
		return $this;
	}

	/**
	 * Auto matically find dependancies for the view definition
	 *
	 * @return View
	 **/
	protected function get_dependant_tables()
	{
		$tables = Autoschema::tables_in_definition();
		if( is_array($tables) ){
			$definition = $this->definition;
			$matches = array();
			foreach ($tables as $key => $val) {
				if( strpos($definition, " $val") ){
					$matches[] = $tables[$key];
				}
			}
			$this->dependant_tables = $matches;
		}
		return $this;
	}
}