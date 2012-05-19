<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * A simple BelongsTo / HasOne association
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jamaker_Attribute_Association extends Jamaker_Attribute {

	protected $strategy = 'build';
	protected $factory;
	protected $overrides;

	function __construct($factory, array $overrides = NULL, $strategy = NULL) 
	{
		$this->factory = $factory;

		if ($strategy !== NULL)
		{
			$this->strategy = $strategy;
		}

		$this->overrides = $overrides;
	}

	/**
	 * @return mixed
	 */
	public function generate($attributes = NULL)
	{
		return Jamaker::generate($this->strategy, $this->factory, $this->overrides);
	}

	public function is_callable()
	{
		return FALSE;
	}
} // End Role Model