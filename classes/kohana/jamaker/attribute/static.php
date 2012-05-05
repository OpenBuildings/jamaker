<?php defined('SYSPATH') OR die('No direct access allowed.');

class Kohana_Jamaker_Attribute_Static extends Jamaker_Attribute {

	protected $value;

	function __construct($value)
	{
		$this->value = $value;
	}

	public function generate()
	{
		return $this->value;
	}
} // End Role Model