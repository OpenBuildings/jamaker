<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * A single trait. This object holds definitions to attribtues and callbacks, 
 * that will be added to the factory in a whole batch.
 * Attributes with the same name will be overwritten, but callbacks will be added.
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jamaker_Trait {

	protected $name;
	protected $attributes;

	/**
	 * 
	 * @param string $name       the name of the trait. will be used to referance this
	 * @param array  $attributes an array of raw attribtues.
	 */
	function __construct($name, array $attributes)
	{
		$this->name = $name;
		$this->attributes = $attributes;
	}

	/**
	 * Get the name
	 */
	public function name()
	{
		return $this->name;
	}

	/**
	 * Get the raw attributes. 
	 */
	public function attributes()
	{
		return $this->attributes;
	}
} // End Role Model