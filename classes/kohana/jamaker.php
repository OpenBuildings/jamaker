<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Jammaker api and maker class
 *
 * @package    Jamaker
 * @author     Ivan Kerin
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jamaker {

	/**
	 * All the defined jamaker definitions
	 * @var array
	 */
	static protected $definitions = array();

	/**
	 * Holds all the makers that have saved objects to the database. This is used to later clean them up.
	 * @var array
	 */
	static protected $created = array();

	static protected $autoloaded = FALSE;

	/**
	 * Define a Jamaker object
	 * @param  string $name       The name of the maker, must be unique
	 * @param  array  $params     If no attributes are defined - use this for attributes
	 * @param  array $attributes  
	 * @return Jamaker             
	 */
	static public function factory($name, array $params, $attributes = NULL)
	{
		Jamaker::autoload();

		if (isset(Jamaker::$definitions[$name]))
			throw new Kohana_Exception('Jamaker jamaker_user already defined');

		return Jamaker::$definitions[$name] = new Jamaker($name, $params, $attributes);
	}

	/**
	 * Automatically include files tests/test_data/jamakers.php and tests/test_data/jamakers/*.php
	 * @return NULL
	 */
	static public function autoload()
	{
		if ( ! Jamaker::$autoloaded)
		{
			$jamakers = Kohana::list_files('tests/test_data/jamakers');
			$jamakers = $jamakers + Kohana::find_file('tests/test_data', 'jamakers', NULL, TRUE);
			foreach ($jamakers as $jamaker_file) 
			{
				require_once $jamaker_file;
			}
			Jamaker::$autoloaded = TRUE;
		}
	}

	/**
	 * Clear all definitions. Useful for testing
	 * @return NULL
	 */
	static public function clear_definitions()
	{
		Jamaker::$definitions = array();
	}

	/**
	 * Clear all created objects in the database so far.
	 * @return NULL 
	 */
	static public function clear_created()
	{
		$models = array();
		foreach (Jamaker::$created as $maker) 
		{
			$models[] = Jelly::model_name(Jamaker::get($maker)->item_class());
		}

		foreach (array_unique(array_filter($models)) as $model) 
		{
			Jelly::query($model)->delete();
		}

		Jamaker::$created = array();
	}

	/**
	 * Get the maker for a given name. Will raise an exception if maker not defined. If you pass a Jamaker object will return it.
	 * @param  mixed $name the name of the maker
	 * @throws Kohana_Exception If a maker with that name is not defined
	 * @return Jamaker       
	 */
	static public function get($name)
	{
		Jamaker::autoload();

		if (is_object($name))
		{
			if ( ! ($name instanceof Jamaker))
				throw new Kohana_Exception("Must be an instance of Jamaker but was :class", array(':class' => get_class($name)));

			return $name;
		}

		if ( ! isset(Jamaker::$definitions[$name]))
			throw new Kohana_Exception('A Jelly Maker with the name ":name" is not defined', array(':name' => $name));
			
		return Jamaker::$definitions[$name];
	}

	/**
	 * Get the attributes for a given definition, used to build / create an item. Useful for passing to controllers.
	 * @param  string $name      The name of the definition to use
	 * @param  array $overrides  Use this to apply last-minute attributes to the generated item. Will overwrite any previous attributes with the same names
	 * @return array             An array of attributes for the item.
	 */
	static public function attributes_for($name, array $overrides = NULL, $strategy = 'build')
	{
		return Jamaker::get($name)->attributes($overrides, $strategy);
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
		$maker = Jamaker::get($name);
		$class = $maker->item_class();
		$item = new $class();

		foreach ($maker->attributes($overrides, $strategy) as $attribute_name => $value) 
		{
			$item->$attribute_name = $value;
		}
		
		$maker->events()->trigger('build.after', $item, array($maker));
		if ($strategy == 'create')
		{
			Jamaker::$created[] = $name;

			$maker->events()->trigger('create.before', $item, array($maker));

			$item->save();

			$maker->events()->trigger('create.after', $item, array($maker));
		}
		return $item;
	}

	/**
	 * Generate a list of items, based on a template.
	 * 
	 * @param  string $strategy  'build' or 'create'
	 * @param  string $name      The name of the definition to use
	 * @param  integer $count    How many objects to generate based on this definition
	 * @param  array  $overrides use this to apply last-minute attributes to the generated item. Will overwrite any previous attributes with the same names
	 * @return array             an array of item objects
	 */
	static public function generate_list($strategy, $name, $count, array $overrides = NULL)
	{
		$list = array();
		foreach (range(1, $count) as $i) 
		{
			$list[] = Jamaker::generate($strategy, $name, $overrides);
		}
		return $list;
	}

	/**
	 * Shorthand for Jamaker::generate('build', $name, $overrides);
	 * @param  string $name
	 * @param  array  $overrides 
	 * @return mixed
	 */
	static public function build($name, array $overrides = NULL)
	{
		return Jamaker::generate('build', $name, $overrides);
	}

	/**
	 * Shorthand for Jamaker::generate('create', $name, $overrides);
	 * @param  string $name
	 * @param  array  $overrides 
	 * @return mixed
	 */
	static public function create($name, array $overrides = NULL)
	{
		return Jamaker::generate('create', $name, $overrides);
	}

	/**
	 * Shorthand for Jamaker::build_list('build', $name, $count, $overrides);
	 * @param  string  $name
	 * @param  integer $count
	 * @param  array   $overrides 
	 * @return mixed
	 */
	static public function build_list($name, $count, array $overrides = NULL)
	{
		return Jamaker::generate_list('build', $name, $count, $overrides);
	}

	/**
	 * Shorthand for Jamaker::create_list('build', $name, $count, $overrides);
	 * @param  string  $name
	 * @param  integer $count
	 * @param  array   $overrides 
	 * @return mixed
	 */
	static public function create_list($name, $count, $overrides = NULL)
	{
		return Jamaker::generate_list('create', $name, $count, $overrides);
	}

	/**
	 * Shorthand for new Jamaker_Attribute_Association($maker, $strategym $overrides);
	 * @param  string  $maker
	 * @param  string  $strategy build or create
	 * @param  array  $overrides
	 * @return Jamaker_Attribute_Association
	 */
	static public function association($maker, $strategy = NULL, array $overrides = NULL)
	{
		return new Jamaker_Attribute_Association($maker, $strategy, $overrides);
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

	/**
	 * Then name of the maker, used to find it
	 * @var string
	 */
	protected $name;

	/**
	 * The class of the items, generated by this definition. Usually a Jelly_Model object
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
	 * Jelly_Event object used for callbacks
	 * @var Jelly_Event
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

		$this->_events = new Jelly_Event($this->name);

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
				$parent = Jamaker::get($this->parent)->initialize();

				$this->class = $parent->class;
				$this->traits = Arr::merge($parent->traits, $this->traits);
				$attributes = (array) $parent->attributes;
			}

			// If class is not defined, guess based on name, if nothing found - use stdClass
			if ( ! $this->class)
			{
				$class = Jelly::class_name($this->name);
				$this->class = class_exists($class) ? $class : 'stdClass';
			}

			$this->_initialized = TRUE;

			// Convert attributes to Jamaker_Attribute objects
			$this->attributes = Arr::merge($attributes, Jamaker_Attribute::convert_all($this, $this->attributes));
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
			elseif ($attribute instanceof Jamaker_Callback) 
			{
				$this->_events->bind($attribute->event(), $attribute->callback());
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

		$attributes = array();

		$overrides = Jamaker_Attribute::convert_all($this, (array) $overrides, $strategy);

		$attributes = Arr::merge($this->attributes, $overrides);

		foreach ($attributes as $name => & $value) 
		{
			$value = $value->generate($attributes);
		}

		return $attributes;
	}

	/**
	 * getter for $_events
	 * @return Jelly_Event 
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
	 * The Jelly_Meta for the current definition
	 * @return Jelly_Meta 
	 */
	public function meta()
	{
		$this->initialize();
		return Jelly::meta($this->class);
	}

	/**
	 * @return string
	 */
	public function name()
	{
		return $this->name;
	}

} // End Role Model