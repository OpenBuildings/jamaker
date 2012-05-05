<?php defined('SYSPATH') OR die('No direct access allowed.');

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