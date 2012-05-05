<?php defined('SYSPATH') OR die('No direct access allowed.');

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