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
	protected $current = NULL;

	function __construct($iterator = NULL, $initial = NULL)
	{
		$this->iterator = $iterator;
		$this->current = $initial;
	}

	/**
	 * Generate the next sequence value
	 * 
	 * @return mixed 
	 */
	public function generate($attributes = NULL, $iteration = 1)
	{
		$current = ($this->current !== NULL) ? $this->current++ : $iteration;

		if ( ! $this->iterator)
		{
			$value = $current;
		}
		elseif (is_array($this->iterator) OR ($this->iterator instanceof ArrayAccess AND $this->iterator instanceof Countable)) 
		{
			$value = $this->iterator[($current-1) % count($this->iterator)];
		}
		elseif (is_string($this->iterator)) 
		{
			$value = str_replace('$n', $current, $this->iterator);
		}

		return $value;
	}

	public function is_callable()
	{
		return FALSE;
	}

} // End Role Model