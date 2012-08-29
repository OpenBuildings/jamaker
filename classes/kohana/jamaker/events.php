<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Extension of Jam Events to handle events cascade
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jamaker_Events extends Jam_Event {

	public static function factory($name, array $callbacks)
	{
		$events = new Jamaker_Events($name);
		foreach ($callbacks as $callback) 
		{
			$events->bind($callback->event(), $callback->callback());
		}
		return $events;
	}
}