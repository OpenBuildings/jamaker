<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Jammaker attribute that returns a differnet predictable value each time.
 * Could be simple iterated integer, a string with '$n' replaced by the current iteration of even a callback method that gets executed each time. 
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jamaker_Attribute_Sequence extends Jamaker_Attribute {

	protected $iterator;
	protected $current = 1;

	function __construct($iterator = NULL, $initial = 1)
	{
		$this->iterator = $iterator;
		$this->current = $initial;
	}

	/**
	 * Generate the next sequence value
	 * 
	 * @return mixed 
	 */
	public function generate($attributes = NULL)
	{
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
			$value = call_user_func($this->iterator, $this->current, $attributes);
		}

		$this->current++;

		return $value;
	}

	public function is_callable()
	{
		return is_callable($this->iterator);
	}

} // End Role Model