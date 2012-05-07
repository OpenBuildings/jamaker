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
	protected $maker;
	protected $overrides;

	function __construct($maker, array $overrides = NULL, $strategy = NULL) 
	{
		$this->maker = $maker;

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
		return is_object($this->maker) ? $this->maker : Jamaker::generate($this->strategy, $this->maker, $this->overrides);
	}
} // End Role Model