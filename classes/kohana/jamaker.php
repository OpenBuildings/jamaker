<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Jammaker api and maker class
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jamaker {

	const MAX_RECURSIVE_DEPTH = 50;

	/**
	 * All the defined jamaker factories
	 * @var array
	 */
	static protected $_factories = array();

	/**
	 * Autoloading should happen only once, based on this variable
	 * @var boolean
	 */
	static protected $_autoload_performed = FALSE;

	/**
	 * Used for checking recursive definitions
	 * @var array
	 */
	static protected $_recursive_depth = array();	

	static protected function _check_recursion($name)
	{
		if ( ! isset(Jamaker::$_recursive_depth[$name]))
		{
			Jamaker::$_recursive_depth[$name] = 0;
		}
		if ( ++Jamaker::$_recursive_depth[$name] > Jamaker::MAX_RECURSIVE_DEPTH)
			throw new Kohana_Exception('Recursive definition detected in :name', array(':name' => $name));
	}

	static public function _check_recursion_finish($name)
	{
		Jamaker::$_recursive_depth[$name] = 0;
	}

	/**
	 * Define a Jamaker object
	 * @param  string $name       The name of the maker, must be unique
	 * @param  array  $params     If no attributes are defined - use this for attributes
	 * @param  array $attributes  
	 * @return Jamaker             
	 */
	static public function define($name, array $params, $attributes = NULL)
	{
		Jamaker::autoload();

		if (isset(Jamaker::$_factories[$name]))
			throw new Kohana_Exception('Jamaker :name already defined', array(':name' => $name));

		return Jamaker::$_factories[$name] = new Jamaker($name, $params, $attributes);
	}

	/**
	 * Automatically include files tests/test_data/jamaker.php and tests/test_data/jamaker/*.php
	 * @return NULL
	 */
	static public function autoload()
	{
		if ( ! Jamaker::$_autoload_performed)
		{
			$jamakers = Kohana::list_files('tests/test_data/jamaker');
			$jamakers = $jamakers + Kohana::find_file('tests/test_data', 'jamaker', NULL, TRUE);
			foreach ($jamakers as $jamaker_file)
			{
				require_once $jamaker_file;
			}
			Jamaker::$_autoload_performed = TRUE;
		}
	}

	/**
	 * * Clear all definitions. Useful for testing
	 * @param  array $_factories Specifically what factories to remove
	 * @return NULL
	 */
	static public function clear_factories(array $_factories = NULL)
	{
		if ($_factories !== NULL)
		{
			foreach ($_factories as $factory) 
			{
				unset(Jamaker::$_factories[$factory]);
			}
		}
		else
		{
			Jamaker::$_factories = array();
		}
	}

	/**
	 * Get the maker for a given name. Will raise an exception if maker not defined. If you pass a Jamaker object will return it.
	 * @param  mixed $name the name of the maker
	 * @throws Kohana_Exception If a maker with that name is not defined
	 * @return Jamaker       
	 */
	static public function factories($name = NULL)
	{
		Jamaker::autoload();

		if ($name !== NULL)
		{
			if (is_object($name))
			{
				if ( ! ($name instanceof Jamaker))
					throw new Kohana_Exception("Must be an instance of Jamaker but was :class", array(':class' => get_class($name)));

				return $name;
			}

			if ( ! isset(Jamaker::$_factories[$name]))
				throw new Kohana_Exception('A Jam Maker with the name ":name" is not defined', array(':name' => $name));
				
			return Jamaker::$_factories[$name];
		}

		return Jamaker::$_factories;
	}

	/**
	 * Get the attributes for a given definition, used to build / create an item. Useful for passing to controllers.
	 * @param  string $name      The name of the definition to use
	 * @param  array $overrides  Use this to apply last-minute attributes to the generated item. Will overwrite any previous attributes with the same names
	 * @return array             An array of attributes for the item.
	 */
	static public function attributes_for($name, array $overrides = NULL, $strategy = 'build')
	{
		$attributes = Jamaker::factories($name)->attributes($overrides, $strategy);
		Jamaker_Attribute::extract_callbacks($attributes);
		return $attributes;
	}

	/**
	 * Generate an object for a jamaker definition. 
	 * Triggers events, if strategy is 'create' - saves the object to the database
	 * 
	 * @param  string $strategy  'build' or 'create'
	 * @param  string $name      The name of the definition to use
	 * @param  array $overrides  Use this to apply last-minute attributes to the generated item. Will overwrite any previos attributes with the same names
	 * @return mixed             The resulting object, Jell_Model
	 */
	static public function generate($strategy, $name, array $overrides = NULL)
	{
		$factory = Jamaker::factories($name);
		$class = $factory->item_class();
		$item = new $class();

		Jamaker::_check_recursion($factory->name());

		$attributes = Jamaker::factories($name)->attributes($overrides, $strategy);
		$callbacks = Jamaker_Attribute::extract_callbacks($attributes);

		$events = Jamaker_Events::factory($factory->name, $callbacks);
	
		foreach ($attributes as $attribute_name => $value) 
		{
			$item->$attribute_name = $value;
		}
		
		$events->trigger('build.after', $item, array($factory, $strategy));

		if ($strategy == 'create')
		{
			$events->trigger('create.before', $item, array($factory, $strategy));

			$item->save();

			$events->trigger('create.after', $item, array($factory, $strategy));
		}

		// Clear recursive depth checker for this factory name
		Jamaker::_check_recursion_finish($factory->name());

		return $item;
	}

	/**
	 * Generate a list of items, based on a template.
	 * 
	 * @param  string $strategy  'build' or 'create'
	 * @param  string $factory   The name of the definition to use, or the actual factory itself
	 * @param  integer $count    How many objects to generate based on this definition
	 * @param  array  $overrides use this to apply last-minute attributes to the generated item. Will overwrite any previous attributes with the same names
	 * @return array             an array of item objects
	 */
	static public function generate_list($strategy, $factory, $count, array $overrides = NULL)
	{
		$list = array();

		if ($overrides)
		{
			$factory = Jamaker::factories($factory)->initialize();
			$overrides = Jamaker_Attribute::convert_all($factory, $overrides, $strategy);
		}

		foreach (range(1, $count) as $i) 
		{
			$list[] = Jamaker::generate($strategy, $factory, $overrides);
		}
		return $list;
	}

	/**
	 * Shorthand for Jamaker::generate('build', $name, $overrides);
	 * @param  string $factory
	 * @param  array  $overrides 
	 * @return mixed
	 */
	static public function build($factory, array $overrides = NULL)
	{
		return Jamaker::generate('build', $factory, $overrides);
	}

	/**
	 * Shorthand for Jamaker::generate('create', $factory, $overrides);
	 * @param  string $factory
	 * @param  array  $overrides 
	 * @return mixed
	 */
	static public function create($factory, array $overrides = NULL)
	{
		return Jamaker::generate('create', $factory, $overrides);
	}

	/**
	 * Shorthand for Jamaker::build_list('build', $factory, $count, $overrides);
	 * @param  string  $factory
	 * @param  integer $count
	 * @param  array   $overrides 
	 * @return mixed
	 */
	static public function build_list($factory, $count, array $overrides = NULL)
	{
		return Jamaker::generate_list('build', $factory, $count, $overrides);
	}

	/**
	 * Shorthand for Jamaker::create_list('build', $factory, $count, $overrides);
	 * @param  string  $factory
	 * @param  integer $count
	 * @param  array   $overrides 
	 * @return mixed
	 */
	static public function create_list($factory, $count, $overrides = NULL)
	{
		return Jamaker::generate_list('create', $factory, $count, $overrides);
	}

	/**
	 * Shorthand for new Jamaker_Attribute_Association($factory, $strategym $overrides);
	 * @param  string  $factory
	 * @param  string  $strategy build or create
	 * @param  array  $overrides
	 * @return Jamaker_Attribute_Association
	 */
	static public function association($factory, array $overrides = NULL, $strategy = NULL)
	{
		return new Jamaker_Attribute_Association($factory, $overrides, $strategy);
	}

	/**
	 * Shorthand for new Jamaker_Attribute_Sequence($iterator);
	 * @param  mixed   $iterator
	 * @param  integer $initial default to 1
	 * @return Jamaker_Attribute_Sequence
	 */
	static public function sequence($iterator = NULL, $initial = 1)
	{
		return new Jamaker_Attribute_Sequence($iterator, $initial);
	}

	/**
	 * Shorthand for new Jamaker_Attribute_Dynamic($iterator);
	 * @param  function   $closure
	 * @return Jamaker_Attribute_Dynamic
	 */
	static public function dynamic_value($closure)
	{
		return new Jamaker_Attribute_Dynamic($closure);
	}

	/**
	 * Shorthand for new Jamaker_Attribute_Static($value);
	 * @param  mixed   $value
	 * @return Jamaker_Attribute_Sequence
	 */
	static public function static_value($value)
	{
		return new Jamaker_Attribute_Static($value);
	}

	/**
	 * Shorthand for new Jamaker_Callback($event.'.before', $callback);
	 * @param  string  $event
	 * @param  Closure  $callback
	 * @return Jamaker_Callback
	 */
	static public function before($event, $callback)
	{
		return new Jamaker_Callback($event.'.before', $callback);
	}

	/**
	 * Shorthand for new Jamaker_Callback($event.'.after', $callback);
	 * @param  string  $event
	 * @param  Closure  $callback
	 * @return Jamaker_Callback
	 */
	static public function after($event, $callback)
	{
		return new Jamaker_Callback($event.'.after', $callback);
	}

	/**
	 * Shorthand for Jamaker_Callback($event.'.before', $callback);
	 * @param  string  $event'
	 * @param  Closure  $callback
	 * @return Jamaker_Callback
	 */
	static public function trait($name, $attributes)
	{
		return new Jamaker_Trait($name, $attributes);
	}

	static public function lorem($chars)
	{
		$text = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n";

		return str_pad('', $chars, $text);
	}


	/**
	 * Then name of the maker, used to find it
	 * @var string
	 */
	protected $name;

	/**
	 * The class of the items, generated by this definition. Usually a Jam_Model object
	 * @var string
	 */
	protected $class;

	/**
	 * The name of the parent maker definition
	 * @var string
	 */
	protected $parent;

	/**
	 * All the attributes for this maker. After initialized() is called - will be populated with Jamaker_Attribute objects
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * A list of available traits for this maker. An array of Jamaker_Trait objects
	 * @var array
	 */
	protected $traits = array();

	/**
	 * Jam_Event object used for callbacks
	 * @var Jam_Event
	 */
	protected $_events;

	/**
	 * Will be set to true after initialize() so that initialize will not be repeated
	 * @var boolean
	 */
	protected $_initialized = FALSE;

	function __construct($name, array $params, $attributes = NULL) 
	{
		if ($attributes === NULL)
		{
			$attributes = $params;
			$params = array();
		}

		$this->name = $name;
		$this->attributes = (array) $attributes;
		$this->parent = Arr::get($params, 'parent');
		$this->attributes = Arr::merge($this->attributes, (array) Arr::get($params, 'traits'));
		$this->class = Arr::get($params, 'class');

		$this->_extract_children();
	}

	/**
	 * Perform all the necessary initializations (will perform them only once)
	 * @return $this
	 */
	public function initialize()
	{
		if ( ! $this->_initialized)
		{
			$attributes = array();

			// Load class, traits, defined_traits and attributes from the parent
			if ($this->parent)
			{
				$parent = Jamaker::factories($this->parent)->initialize();

				$this->class = $parent->class;
				$this->traits = Arr::merge($parent->traits, $this->traits);
				$attributes = (array) $parent->attributes;
			}

			// If class is not defined, guess based on name, if nothing found - use stdClass
			if ( ! $this->class)
			{
				$class = Jam::class_name($this->name);
				$this->class = class_exists($class) ? $class : 'stdClass';
			}

			$this->_initialized = TRUE;

			// Convert attributes to Jamaker_Attribute objects
			$this->attributes = Jamaker_Attribute::convert_all($this, $this->attributes); 

			// Add attributes from parent that are missing in the child, keeping correct order
			$this->attributes = $this->attributes + array_diff_key($attributes, $this->attributes);

		}
		return $this;
	}

	/**
	 * Evaluate attributes from the attributes array and remove them if they are not actually attributes but configuration options.
	 * @return NULL           
	 */
	private function _extract_children()
	{
		foreach ($this->attributes as $name => $attribute) 
		{
			if ($attribute instanceof Jamaker_Trait)
			{
				$this->traits[$attribute->name()] = $attribute;
				unset($this->attributes[$name]);
			}
			elseif ($attribute instanceof Jamaker) 
			{
				$attribute->parent = $this;
				unset($this->attributes[$name]);
			}
		}
	}

	/**
	 * Return attributes for an item based on this definition.
	 * @param  array $overrides 
	 * @return array            
	 */
	public function attributes(array $overrides = NULL, $strategy = 'build')
	{
		$this->initialize();

		$attributes = $this->attributes;

		$overrides = Jamaker_Attribute::convert_all($this, (array) $overrides, $strategy);
		
		$attributes = Jamaker_Attribute::merge($attributes, $overrides);

		// Generate all static attributes
		foreach ($attributes as $name => & $value) 
		{
			if (($value instanceof Jamaker_Attribute) AND ! $value->is_callable())
			{
				$value = $value->generate($attributes);
			}
		}

		// Generate all dinamic attributes
		foreach ($attributes as $name => & $value) 
		{
			if ($value instanceof Jamaker_Attribute)
			{
				$value = $value->generate($attributes);
			}
		}

		return $attributes;
	}

	/**
	 * getter for $_events
	 * @return Jam_Event 
	 */
	public function events()
	{
		return $this->_events;
	}

	/**
	 * Get a trait for this maker
	 * @return array 
	 */
	public function traits($name = NULL)
	{
		$this->initialize();

		if ($name !== NULL)
		{
			if ( ! isset($this->traits[$name]))
				throw new Kohana_Exception('A trait with the name ":trait" does not exist for factory ":factory"', array(':trait' => $name, ':factory' => $this->name));

			return $this->traits[$name];
		}

		return $this->traits;
	}

	/**
	 * @return string 
	 */
	public function parent()
	{
		return $this->initialize()->parent;
	}

	/**
	 * @return boolean 
	 */
	public function initialized()
	{
		return $this->_initialized;
	}

	/**
	 * @return string 
	 */
	public function item_class()
	{
		return $this->initialize()->class;
	}

	/**
	 * The Jam_Meta for the current definition
	 * @return Jam_Meta 
	 */
	public function meta()
	{
		$this->initialize();
		return Jam::meta($this->class);
	}

	/**
	 * @return string
	 */
	public function name()
	{
		return $this->name;
	}

} // End Role Model