<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Super class for all the Jamaker_Attributes. 
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jamaker_Attribute {

	abstract public function generate($attributes = NULL);

	/**
	 * Convert an array of attributes with shorthands for traits and attributes to a clean array of Jamaker_Attribute objects
	 * @param  Jamaker $factory     The context maker
	 * @param  array $attributes 
	 * @param  string $strategy   The current build strategy
	 * @return array             
	 */
	static public function convert_all($factory, $attributes, $strategy = 'build')
	{
		$converted = array();

		foreach ($attributes as $name => $attribute)
		{
			// Extract callbacks and add them back to the factory in the same order	
			if ($attribute instanceof Jamaker_Callback)
			{
				$factory->add_callback($attribute);
				continue;
			}

			// Extract attributes from traits, 
			// merging them back to attribtues array in the correct order
			if (is_numeric($name) AND is_string($attribute))
			{
				$trait_attributes = $factory->traits($attribute)->attributes();
				$converted = Arr::merge($converted, Jamaker_Attribute::convert_all($factory, $trait_attributes, $strategy));
				continue;
			}

			// All the attributes that are left 
			// must be converted to Jamaker_Attribtue objects
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