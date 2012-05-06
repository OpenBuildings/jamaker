<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * A single trait (collection of attributes)
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jamaker_Trait {

	protected $name;
	protected $attributes;

	function __construct($name, $attributes)
	{
		$this->name = $name;
		$this->attributes = $attributes;
	}

	public function name()
	{
		return $this->name;
	}

	public function attributes()
	{
		return $this->attributes;
	}
} // End Role Model