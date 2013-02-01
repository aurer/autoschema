<?php namespace AutoSchema;

use \Laravel\Form;
use \Laravel\Database as DB;

class AutoForm{

	protected $definition;

	/**
	 * Set the definition of the form element
	 *
	 * @return void
	 **/
	public function __construct($definition)
	{
		$this->definition = $definition;
	}

	/**
	 * 
	 *
	 * @return AutoForm
	 **/
	public static function field($table, $column, $html5=false)
	{
		$definition = AutoSchema::column_in_definition($table, $column);
		$definition['type'] = self::translate( $definition['type'], $html5 );
		$definition['value'] = null;

		// Build values array if it exists
		if( isset($definition['values']) ){
			$values = $definition['values'];
			if( is_array($values) ){
				$definition['type'] = 'select'; // Change type to a select
			}
			// Should we get values from a specified table e.g. 'table:column1,column2'
			elseif( preg_match('/^([a-z_]+):([a-z_0-9]+),?([a-z_0-9]+)?$/', $values, $matches) ){
				$table = $matches[1];
				$col1 = $matches[2];
				$col2 = isset($matches[3]) ? $matches[3] : null;
				$result = isset($matches[3]) ? DB::query("SELECT $col1, $col2 FROM $table") : DB::query("SELECT $col1 FROM $table");

				$definition['type'] = 'select';
				$definition['values'] = array();
				foreach ($result as $key => $value) {
					if( $col2 ) $definition['values'][$value->$col1] = $value->$col2;
					else $definition['values'][$key] = $value->$col1;
				}
				$definition['values'];
			}
		}

		$definition['attributes'] = array(
			'id' => 'in-'.$definition['name'],
			'class' => 'type-'.$definition['type'],
		);

		return new AutoForm($definition);
	}

	/**
	 * Builds the HTML form element based on a predefined definition
	 *
	 * @return string
	 **/
	public function element()
	{	
		extract($this->definition);
		
		if( $type == 'textarea' ){
			return Form::textarea($name, $value, $attributes);
		}
		elseif( $type == 'checkbox' || $type == 'radio' ){
			return Form::$type($name, $value, $attributes);
		}
		elseif( $type == 'select' ){
			return Form::select($name, $values, $value, $attributes);
		}
		else{
			return Form::input($type, $name, $value, $attributes);
		}
	}

	/**
	 * Create a full form field with label and markup
	 *
	 * @return string
	 **/
	public function form_field()
	{
		extract($this->definition);
		$html = '<div class="field type-' . $type . '">' . "\n";
		$html .= "\t" . '<div class="label">' . Form::label($attributes['id'], $label) . '</div>' . "\n";
		$html .= "\t" . '<div class="input">' . $this->element() . '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	/**
	 * Change the form element type
	 *
	 * @return AutoForm
	 **/
	public function type($string)
	{
		$this->definition['type'] = $string;
		return $this;
	}

	/**
	 * Change the form label
	 *
	 * @return AutoForm
	 **/
	public function label($string)
	{
		$this->definition['label'] = $string;
		return $this;
	}

	/**
	 * Change the value for the form element
	 *
	 * @return AutoForm
	 **/
	public function value($string)
	{
		$this->definition['value'] = $string;
		return $this;
	}

	/**
	 * Change the values for the form element
	 *
	 * @return AutoForm
	 **/
	public function values($array)
	{
		$this->definition['values'] = $array;
		return $this;
	}

	/**
	 * Set attributes for the form element
	 *
	 * @return AutoForm
	 **/
	public function attr($array)
	{
		$this->definition['attributes'] = array_merge($this->definition['attributes'], $array);
		return $this;	
	}

	/**
	 * Translate between the schema type and form element types
	 *
	 * @param  type string
	 * @param  html boolean
	 * @return string
	 * @author Phil Maurer
	 **/
	protected static function translate($type, $html5=false)
	{
		$default = 'text';
		$html = array(
			'string'	=> 'text',
			'integer'	=> 'text',
			'float'		=> 'text',
			'decimal'	=> 'text',
			'text'		=> 'textarea',
			'boolean'	=> 'checkbox',
			'date'		=> 'text',
			'timestamp'	=> 'text',
			'blob'		=> 'file',
		);

		$html5 = array(
			'string'	=> 'text',
			'integer'	=> 'number',
			'float'		=> 'number',
			'decimal'	=> 'number',
			'text'		=> 'textarea',
			'boolean'	=> 'checkbox',
			'date'		=> 'date',
			'timestamp'	=> 'date',
			'blob'		=> 'file',
		);

		if( !array_key_exists($type, $html) && !array_key_exists($type, $html5) ){
			return $default;
		}
		if( $html5 === true ){
			return $html5[$type];
		} else {
			return $html[$type];
		}
	}

	public function __call($name, $value)
	{
		$this->definition['attributes'][$name] = $value[0];
		return $this;
	}
}

//