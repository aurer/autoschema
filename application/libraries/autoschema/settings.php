<?php namespace AutoSchema;

class Settings{
	function __call($name, $args)
	{
		if( count($args) == 1){
			$this->$name = $args[0];
		}
		elseif( count($args) > 1 ){
			$args = func_get_args();
			$this->$args[0] = $args[1];
		}
		return $this;
	}
}