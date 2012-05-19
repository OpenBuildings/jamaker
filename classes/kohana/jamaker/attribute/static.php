<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Basic attribute - holds a single static value
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */

class Kohana_Jamaker_Attribute_Static extends Jamaker_Attribute {

	protected $value;

	function __construct($value)
	{
		$this->value = $value;
	}

	public function generate($attributes = NULL)
	{
		return $this->value;
	}

	public function is_callable()
	{
		return FALSE;
	}

} // End Role Model