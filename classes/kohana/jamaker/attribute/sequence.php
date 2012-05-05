<?php defined('SYSPATH') OR die('No direct access allowed.');

class Kohana_Jamaker_Attribute_Sequence extends Jamaker_Attribute {

	protected $iterator;
	protected $current = 0;

	function __construct($iterator = NULL)
	{
		$this->iterator = $iterator;
	}

	public function generate()
	{
		$this->current++;

		if ( ! $this->iterator)
		{
			$value = $this->current;
		}
		elseif (is_array($this->iterator) OR ($this->iterator instanceof ArrayAccess AND $this->iterator instanceof Countable)) 
		{
			$value = $this->iterator[($this->current-1) % count($this->iterator)];
		}
		elseif (is_string($this->iterator)) 
		{
			$value = str_replace('$n', $this->current, $this->iterator);
		}
		elseif (is_callable($this->iterator))
		{
			$value = call_user_func($this->iterator, $this->current);
		}

		return $value;
	}
} // End Role Model