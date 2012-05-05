<?php defined('SYSPATH') OR die('No direct access allowed.');

abstract class Kohana_Jamaker {

	static public $makers = array();
	static public $created = array();

	static public function define($name, array $params, $fields = NULL)
	{
		return Jamaker::$makers[$name] = new Jamaker($name, $params, $fields);
	}

	static public function get($name)
	{
		if (is_object($name))
		{
			if ( ! ($name instanceof Jamaker))
				throw new Kohana_Exception("Must be an instance of Jamaker but was :class", array(':class' => get_class($name)));

			return $name;
		}

		if ( ! isset(Jamaker::$makers[$name]))
			throw new Kohana_Exception('A Jelly Maker with the name ":name" is not defined', array(':name' => $name));
			
		return Jamaker::$makers[$name];
	}

	static public function generate($strategy, $name, $overrides = NULL)
	{
		$maker = Jamaker::get($name);
		$item = Jelly::build($maker->model())->set($maker->attributes($overrides));
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

	static public function generate_list($strategy, $name, $count, $overrides)
	{
		$list = array();
		foreach (range(1, $count) as $i) 
		{
			$list[] = Jamaker::generate($strategy, $name, $overrides);
		}
		return $list;
	}

	static public function build($name, $overrides = NULL)
	{
		return Jamaker::generate('build', $name, $overrides);
	}

	static public function create($name, $overrides = NULL)
	{
		return Jamaker::generate('create', $name, $overrides);
	}

	static public function build_list($name, $count, $overrides = NULL)
	{
		return Jamaker::generate_list('build', $name, $count, $overrides);
	}

	static public function create_list($name, $count, $overrides = NULL)
	{
		return Jamaker::generate_list('create', $name, $count, $overrides);
	}

	static public function association($maker, $params = NULL)
	{
		return new Jamaker_Attribute_Association($maker, $params);
	}

	static public function sequence($iterator = NULL)
	{
		return new Jamaker_Attribute_Sequence($iterator);
	}

	static public function before($event, $callback)
	{
		return new Jamaker_Callback($event.'.before', $callback);
	}

	static public function after($event, $callback)
	{
		return new Jamaker_Callback($event.'.after', $callback);
	}

	static public function trait($name, $attributes)
	{
		return new Jamaker_Trait($name, $attributes);
	}

	static public function attributes_for($name, $overrides)
	{
		return Jamaker::maker($name)->attributes($overrides);
	}

	static public function clear_definitions()
	{
		Jamaker::$makers = array();
	}

	static public function clear_created()
	{
		$models = array();
		foreach (Jamaker::$created as $maker) 
		{
			$models[] = Jamaker::get($maker)->model();
		}
		$models = array_unique($models);

		foreach ($models as $model) 
		{
			Jelly::query($model)->delete();
		}

		Jamaker::$created = array();
	}

	protected $name;
	protected $model;
	protected $attributes = array();
	protected $defined_traits = array();
	protected $traits = array();
	protected $parent;

	protected $_events;

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
		$this->traits = (array) Arr::get($params, 'traits');
		$this->model = Arr::get($params, 'model');

		$this->_set_parent_to_children();
	}

	public function initialize()
	{
		if ( ! $this->_initialized)
		{
			$attributes = array();

			if ($this->parent)
			{
				$parent = Jamaker::get($this->parent)->initialize();

				$this->model = $parent->model;
				$this->traits = Arr::merge($parent->traits, $this->traits);
				$this->defined_traits = Arr::merge($parent->defined_traits, $this->defined_traits);
				$attributes = (array) $parent->attributes;
			}

			$this->model = $this->model ? $this->model : $this->name;

			$this->_convert_callbacks();
			$this->_convert_traits();

			foreach ($this->traits as $trait) 
			{
				$attributes = Arr::merge($attributes, $this->defined_traits[$trait]->attributes());
			}

			$this->attributes = Arr::merge($attributes, $this->attributes);

			$this->_initialized = TRUE;

			foreach ($this->attributes as $name => & $value)
			{
				$value = Jamaker_Attribute::factory($this, $name, $value);
			}
		}
		return $this;
	}

	private function _convert_traits()
	{
		foreach ($this->attributes as $name => $attribute) 
		{
			if ($attribute instanceof Jamaker_Trait)
			{
				$this->defined_traits[$attribute->name()] = $attribute;

				unset($this->attributes[$name]);
			}
			elseif (is_numeric($name) AND is_string($attribute))
			{
				$this->traits[] = $attribute;

				unset($this->attributes[$name]);
			}
		}
	}

	private function _convert_callbacks()
	{
		$this->events = new Jelly_Event($this->model);

		foreach ($this->attributes as $name => $attribute) 
		{
			if ($attribute instanceof Jamaker_Callback)
			{
				$this->events->bind($attribute->event(), $attribute->callback());

				unset($this->attributes[$name]);
			}
		}
	}

	private function _set_parent_to_children()
	{
		foreach ($this->attributes as $name => $attribute) 
		{
			if ($attribute instanceof Jamaker)
			{
				$attribute->parent = $this;

				unset($this->attributes[$name]);
			}
		}
	}

	public function attributes(array $overrides = NULL)
	{
		$this->initialize();
		$attributes = array();
		$overrides = (array) $overrides;

		foreach ($overrides as $name => & $value) 
		{
			$value = Jamaker_Attribute::factory($this, $name, $value);
		}

		$attributes = Arr::merge($this->attributes, $overrides);

		foreach ($attributes as $name => & $value) 
		{
			$value = $value->generate();
		}

		return $attributes;
	}


	public function events()
	{
		return $this->events;
	}

	public function traits()
	{
		return $this->initialize()->traits;
	}

	public function parent()
	{
		return $this->initialize()->parent;
	}

	public function initialized()
	{
		return $this->_initialized;
	}

	public function model()
	{
		return $this->initialize()->model;
	}

	public function meta()
	{
		$this->initialize();
		$meta = Jelly::meta($this->model);

		if ( ! $meta)
			throw new Kohana_Exception('Model :model does not exist for Jamaker :name', array(':model' => $this->model, ':name' => $this->name));

		return $meta;
	}

	public function name()
	{
		return $this->name;
	}

} // End Role Model