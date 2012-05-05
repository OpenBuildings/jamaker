<?php defined('SYSPATH') OR die('No direct access allowed.');

abstract class Kohana_Jamaker_Attribute {

	abstract public function generate();

	static public function factory(Jamaker $maker, $name, $value)
	{
		if ( ! ($value instanceof Jamaker_Attribute))
		{
			$class = 'Jamaker_Attribute_Static'; 

			if (is_callable($value) OR (is_string($value) AND strpos($value, '$n') !== FALSE))
			{
				$class = 'Jamaker_Attribute_Sequence';
			}
			elseif ($maker->meta()->association($name))
			{
				$class = 'Jamaker_Attribute_Association';
			}

			$value = new $class($value);
		}

		return $value;
	}

}