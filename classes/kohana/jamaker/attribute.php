<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Super class for all the Jamaker_Attributes. 
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jamaker_Attribute {

	/**
	 * Generate the actual content of the atribute, iterators iterate, callables are called
	 * @param array $attributes The attributes so far
	 */
	abstract public function generate($attributes = NULL);

	/**
	 * Return if the attribute is callable. Callabales are generated later than non-callables
	 * @return boolean
	 */
	abstract function is_callable();

	/**
	 * Get the callbacks from attributes, and remove them from the array itself
	 * @param array $attributes 
	 * @return  array callbacks array
	 */
	public static function extract_callbacks(array & $attributes)
	{
		$callbacks = array();

		foreach ($attributes as $name => $attribute)
		{
			// Extract callbacks and add them back to the factory in the same order	
			if ($attribute instanceof Jamaker_Callback)
			{
				$callbacks[] = $attribute;
				unset($attributes[$name]);
			}
		}
		return $callbacks;
	}

	public static function merge(array $array1, array $array2)
	{
		foreach ($array2 as $name => $attribute) 
		{
			if ( ! is_numeric($name))
			{
				unset($array1[$name]);
			}
		}
		return array_merge($array1, $array2);
	}

	/**
	 * Convert an array of attributes with shorthands for traits and attributes to a clean array of Jamaker_Attribute objects
	 * @param  Jamaker $factory     The context maker
	 * @param  array $attributes 
	 * @param  string $strategy   The current build strategy
	 * @return array             
	 */
	public static function convert_all($factory, $attributes, $strategy = 'build')
	{
		$converted = array();

		foreach ($attributes as $name => $attribute)
		{
			// Extract attributes from traits, 
			// merging them back to attribtues array in the correct order
			if (is_numeric($name) AND is_string($attribute))
			{
				$trait_attributes = $factory->traits($attribute)->attributes();
				$trait_attributes = Jamaker_Attribute::convert_all($factory, $trait_attributes, $strategy);
				$converted = Jamaker_Attribute::merge($converted, $trait_attributes);
				continue;
			}

			if ($attribute instanceof Jamaker_Callback)
			{
				$converted[] = $attribute;
				continue;
			}

			// All the attributes that are left 
			// must be converted to Jamaker_Attribute objects
			if ( ! ($attribute instanceof Jamaker_Attribute))
			{
				// Methods like array($object, 'method'), 'Class::method', 'some_function_name' or Closure objects
				if (is_callable($attribute))
				{
					$attribute = new Jamaker_Attribute_Dynamic($attribute);	
				}
				// Arrays or strings with '$n' in them
				elseif (is_array($attribute) OR (is_string($attribute) AND strpos($attribute, '$n') !== FALSE))
				{
					$attribute = new Jamaker_Attribute_Sequence($attribute);	
				}
				// Strings matching association name and jamaker factory
				elseif (is_string($attribute) AND $factory->meta() AND $factory->meta()->association($name))
				{
					$attribute = new Jamaker_Attribute_Association($attribute, array(), $strategy);	
				}
				// Everything else
				else
				{
					$attribute = new Jamaker_Attribute_Static($attribute);	
				}
			}
			$converted[$name] = $attribute;
		}

		return $converted;
	}

}