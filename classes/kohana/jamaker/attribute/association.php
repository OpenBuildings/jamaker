<?php defined('SYSPATH') OR die('No direct access allowed.');

class Kohana_Jamaker_Attribute_Association extends Jamaker_Attribute {

	protected $strategy = 'build';
	protected $maker;

	function __construct($maker, $params = NULL) 
	{
		$this->maker = $maker;

		if ( ! empty($params['strategy']))
		{
			$this->strategy = $params['strategy'];
		}
	}

	public function generate()
	{
		return Jamaker::generate($this->strategy, $this->maker);
	}
} // End Role Model