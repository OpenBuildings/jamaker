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
	 * @param  Jamaker $maker     The context maker
	 * @param  array $attributes 
	 * @param  string $strategy   The current build strategy
	 * @return array             
	 */
	static public function convert_all($maker, $attributes, $strategy = 'build')
	{
		$converted = array();

		foreach ($attributes as $name => $attribute)
		{
			// Convert traits by keeping the precedence
			if (is_numeric($name) AND is_string($attribute))
			{
				$trait_attributes = $maker->traits($attribute)->attributes();
				$converted = Arr::merge($converted, Jamaker_Attribute::convert_all($maker, $trait_attributes, $strategy));
				continue;
			}

			// Convert shorthands
			if ( ! ($attribute instanceof Jamaker_Attribute))
			{
				if (is_callable($attribute))
				{
					$attribute = new Jamaker_Attribute_Dynamic($attribute);	
				}
				elseif (is_string($attribute) AND strpos($attribute, '$n') !== FALSE)
				{
					$attribute = new Jamaker_Attribute_Sequence($attribute);	
				}
				elseif ($maker->meta() AND $maker->meta()->association($name))
				{
					$attribute = new Jamaker_Attribute_Association($attribute, array(), $strategy);	
				}
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