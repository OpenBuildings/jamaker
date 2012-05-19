<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Jammaker attribute that runs a callback and evaluates each time it is requested
 * Gets the current generated attributes as first argument
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jamaker_Attribute_Dynamic extends Jamaker_Attribute {

	protected $callback = 0;

	function __construct($callback)
	{
		$this->callback = $callback;
	}

	/**
	 * Generate the next sequence value
	 * 
	 * @return mixed 
	 */
	public function generate($attributes = NULL)
	{
		return call_user_func($this->callback, $attributes);
	}

	public function is_callable()
	{
		return TRUE;
	}
} // End Role Model