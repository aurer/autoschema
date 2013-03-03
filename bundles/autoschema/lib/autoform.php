<?php namespace AutoSchema;

use \Laravel\Form;
use \Laravel\Database as DB;

class AutoForm{

	protected $definition;

	/**
	 * Set the definition of the form element
	 *
	 * @param  object $definition
	 * @return void
	 **/
	public function __construct($definition)
	{
		$this->definition = $definition;
	}

	/**
	 * Create a new instance of this class allowing it to be created via a static call.
	 *
	 * @param  string 	$table
	 * @return array
	 */
	public static function field($table, $column, $showall=false)
	{
		$definition = AutoSchema::column_in_definition($table, $column);
		return new AutoForm((object)$definition);
	}

	/**
	 * Return the columns for a given table.
	 *
	 * @param  string 	$table
	 * @return array
	 */
	public static function table_rules($table)
	{
		$definition = AutoSchema::get_table_definition($table);
		$rules = array();
		foreach ($definition->columns as $column) {
			if( isset($column['rules']) ){
				$rules[$column['name']] = $column['rules'];
			}
			if( isset($column['type']) && $column['type'] == 'integer' ){
				if( isset($rules[$column['name']]) ) {
					$rules[$column['name']] .= '|numeric';
				} else {
					$rules[$column['name']] = 'numeric';
				}
			}
		}
		return $rules;
	}

	/**
	 * Filter input from a request to prepare it for an insert or update statment
	 *
	 * @param  string $table
	 * @param  array  $input
	 * @return array
	 */
	public static function filter_input($table, $input=array())
	{
		$definition = AutoSchema::get_table_definition($table);
		
		$filtered = array();
		foreach ($definition->columns as $column) {
			$name = $column['name'];
			if( isset($input[$name]) ){
				$filtered[$name] = $input[$name];
			}
			if( $column['type'] == 'boolean' ){
				$filtered[$name] = isset($input[$name]) ? true : false;
			}
		}

		return $filtered;
	}

	/**
	 * Builds the HTML form element based on a predefined definition
	 *
	 * @return string
	 **/
	public function form_element($definition, $value=null, $fallback=null)
	{	
		if( is_null($value) ){
			$value = $fallback;
		}
		
		if( is_null($value) ){
			$value = $definition->value;
		}

		if( $definition->type == 'textarea' ){
			return Form::textarea($definition->name, $value, $definition->attributes);
		}
		elseif( $definition->type == 'select' ){
			return Form::select($definition->name, $definition->values, $value, $definition->attributes);
		}
		else{
			return Form::input($definition->type, $definition->name, $value, $definition->attributes);
		}
	}

	/**
	 * Create a full form field with label and markup
	 *
	 * @return string
	 **/
	public function form_field($value=null)
	{
		foreach (array_reverse(func_get_args()) as $arg) {
			if( isset($arg) ){
				$value = $arg;
				break;
			}
		}
		
		$errors = \Laravel\Session::get('errors');
		$definition = $this->translate_for_form($this->definition);
		$html = '<div class="field type-' . $definition->type . '">' . "\n";
		$html .= "\t" . '<div class="label">' . Form::label($definition->attributes['id'], $definition->label) . '</div>' . "\n";
		$html .= "\t" . '<div class="input">' . $this->form_element($definition, $value) . '</div>' . "\n";
		if( $errors && $errors->has( $definition->name ) ){
			$html .= '<div class="error">' . $errors->first( $definition->name ) . '</div>';
		}
		$html .= '</div>' . "\n";
		return $html;
	}

	/**
	 * Translates a column definition for use in a form
	 *
	 * @return object
	 **/
	protected function translate_for_form($definition)
	{
		$definition->type = self::translate( $definition->type );
		$definition->value = null;

		// Build values array if it exists
		if( isset($definition->values) ){
			$values = $definition->values;
			if( is_array($values) ){
				$definition->type = 'select'; // Change type to a select
			}
			// Should we get values from a specified table e.g. 'table:column1,column2'
			elseif( preg_match('/^([a-z_]+):([a-z_0-9]+),?([a-z_0-9]+)?$/', $values, $matches) ){
				$table = $matches[1];
				$col1 = $matches[2];
				$col2 = isset($matches[3]) ? $matches[3] : null;
				$result = isset($matches[3]) ? DB::query("SELECT $col1, $col2 FROM $table") : DB::query("SELECT $col1 FROM $table");

				$definition->type = 'select';
				$definition->values = array();
				foreach ($result as $key => $value) {
					if( $col2 ) $definition->values[$value->$col1] = $value->$col2;
					else $definition->values[$key] = $value->$col1;
				}
				$definition->values;
			}
		}
		if( $definition->name == 'password' || (isset($definition->password) && $definition->password == true) ){
			$definition->type = 'password';
		}

		$definition->attributes = array(
			'id' => 'in-'.$definition->name,
			'class' => 'type-'.$definition->type,
		);
		return $definition;
	}

	/**
	 * Create a full form field with label and markup
	 *
	 * @return string
	 **/
	public function rules()
	{
		return isset( $this->definition->rules ) ? $this->definition->rules : null;
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

	/**
	 * Allow overloaded calls to set an attribute on the form class
	 *
	 * @param  string 	$table
	 * @return array
	 */
	public function __call($name, $value)
	{
		$this->definition['attributes'][$name] = $value[0];
		return $this;
	}
}

//