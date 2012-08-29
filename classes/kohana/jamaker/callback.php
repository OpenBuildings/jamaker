<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * A callback to be called before / after events (build or create)
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jamaker_Callback {

	protected $event;
	protected $callback;

	function __construct($event, $callback)
	{
		$this->event = $event;
		$this->callback = $callback;
	}

	public function event()
	{
		return $this->event;
	}

	public function callback()
	{
		return $this->callback;
	}
} // End Role Model