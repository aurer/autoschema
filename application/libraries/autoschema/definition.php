<?php namespace AutoSchema;

class Definition
{
	public $name;
	public $pk;

	/**
	 * The columns that should be added to the table.
	 *
	 * @var array
	 */
	public $columns = array();

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function as_array()
	{
		if( empty($this->pk) ){
			$this->pk = $this->columns[0]['name'];
		}
		return array(
			'name' => $this->name,
			'primary_key'	=> $this->pk,
			'columns' => $this->columns,
		);
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
		$this->pk($name);
		return $this->integer($name, true);
	}

	public function timestamps()
	{
		$this->date('created_at');

		$this->date('updated_at');
	}

	public function pk($arg)
	{	
		$this->pk = $arg;
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

	public function attributes($arg)
	{	
		$col = array_pop($this->columns);
		
		foreach ($arg as $key => $value) {
			$col[$key] = $value;
		}
		
		$this->columns[] = $col;
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
}
