Getting Started
===============

Defining factories
------------------

Each factory has a name and a set of attributes. The name is used to guess the class of the object by default, but it's possible to explicitly specify it:

```php
<?php
// This will guess the Model_User class
Jamaker::factory('user', array(
	'first_name' => 'John',
	'last_name' => 'Doe',
	'admin' => FALSE,
));

// This will use the User class (Model_Admin would have been guessed)
Jamaker::factory('admin', array('class' => 'Model_User'), array(
	'first_name' => 'Admin',
	'last_name' => 'User',
	'admin' => TRUE,
));
?>
```

It is highly recommended that you have one factory for each class that provides the simplest set of attributes necessary to create an instance of that class. If you're creating Jelly objects, that means that you should only provide attributes that are required through validations and that do not have defaults. Other factories can be created through inheritance to cover common scenarios for each class.

Attempting to define multiple factories with the same name will raise an error.

Factories can be defined anywhere, but will be automatically loaded if they
are defined in files at the following locations:

	APPPATH/tests/test_data/jamaker.php
	MODPATH/{module name}/tests/test_data/jamaker.php

	APPPATH/tests/test_data/jamaker/*.php
	MODPATH/{module name}/tests/test_data/jamaker/*.php

Using factories
---------------

Jamaker supports several different build strategies: build, create, and attributes\_for:

```php
<?php
// Returns a Model_User instance that's not saved
$user = Jamaker::build('user');

// Returns a saved Model_User instance
$user = Jamaker::create('user');

// Returns a hash of attributes that can be used to build a User instance
$attrs = Jamaker::attributes_for('user');
?>
```

No matter which strategy is used, it's possible to override the defined attributes by passing an array:

```php
<?
// Build a User instance and override the first_name property
$user = Jamaker::build('user', array('first_name' => 'Joe'));
echo $user->first_name; // 'Joe'
?>
```

Lazy Attributes
---------------

Most factory attributes can be added using static values that are evaluated when the factory is defined, but some attributes (such as associations and other attributes that must be dynamically generated) will need values assigned each time an instance is generated. These "lazy" attributes can be added by passing a function instead of a value:

```php
<?php
Jamaker::factory('user', array(
	// ...
	'title' => 'Title',
	'activation_code' => function(){ return Model_User::generate_activation_code(); },
	'date_of_birth' => function(){ return strtotime('-21 years'); },
));
?>
```

Dependent Attributes
--------------------

```php
<?php
Jamaker::factory('user', array(
	'first_name' => 'Joe',
	'last_name' => 'Blow'
	'email' => function($attrs) { return strtolower($attrs['first_name'].'.'.$attrs['last_name'].'@example.com') }
));

echo Jamaker::create('user', array('last_name' => 'Doe'))->email; // 'joe.doe@example.com'
?>
```

The first argument of the function is an array of attributes that have been evaluated up to this point. You will not be able to get attributes further down in the evaluation.

You can use any method to define callable methods, 'Model_User::convert' and array($object, 'method') can be used for dynamic evalutaion

Associations
------------

It's possible to set up associations within factories. You specify the name of the factory for this association with the value.

```php
<?php
Jamaker::factory('user', array(
	// ...
));

Jamaker::factory('post', array(
	'author' => 'user'
));
?>
```

You can also specify a different factories or override attributes:

```php
<?php
Jamaker::factory('post', array(
	'author' => Jamaker::association('admin_user', array('last_name' => 'Nemo'), 'build')
));
?>
```

The behavior of the association method varies depending on the build strategy used for the parent object.

```php
<?php
// Builds and saves a Model_User and a Model_Post
$post = Jamaker::create('post');
echo $post->loaded();                  // TRUE
echo $post->author->loaded();          // TRUE

// Only build both Model_User and Model_Post
$post = Jamaker::build('post');
echo $post->loaded();                  // FALSE
echo $post->author->loaded();          // FALSE
?>
```

Generating data for a `hasmany` relationship is a bit more involved, depending on the amount of flexibility desired, but here's a surefire example of generating associated data.

```php
<?php

// post factory with a `belongsto` association for the user
Jamaker::factory('post', array(
	'title' => 'Through the Looking Glass',
	'user' => 'user',
));

Jamaker::factory('user', array(
	'name' => 'John Doe',

	Jamaker::factory('user_with_posts', array(
		// This value does not exit in the database and will not be saved, but we can use it to pass variables around
		'_posts_count' = 5,

		Jamaker::after('create', function($user){
			$user->posts = Jamaker::create_list('post', $user->_posts_count, array('user' => $user));
		});
	));
));


echo Jamaker::create('user')->posts; // Jelly_Collection: Model_Post(0)
echo Jamaker::create('user_with_posts')->posts; // Jelly_Collection: Model_Post(5)
echo Jamaker::create('user_with_posts', array('_posts_count' => 2))->posts; // Jelly_Collection: Model_Post(2)
?>
```

Inheritance
-----------

You can easily define multiple factories for the same class without repeating common attributes with nesting factories:

```php
<?php
Jamaker::factory('post', array(
	'title' => 'A Title',

	Jamaker::factory('approved_post', array(
		'approved' = TRUE,
	));
));

$approved_post = Jamaker::create('approved_post');
echo $approved_post->title; // "A title"
echo $approved_post->approved; // TRUE
?>
```

You can also assign the parent explicitly:

```php
<?php
Jamaker::factory('post', array(
	'title' => 'A Title',
));

Jamaker::factory('approved_post', array('parent' => 'post'), array(
	'approved' = TRUE,
));
?>
```

As mentioned above, it's good practice to define a basic factory for each class with only the attributes required to create it. Then, create more specific factories that inherit from this basic parent. Factory definitions are still code, so keep them DRY.

Sequences
---------

Unique values in a specific format (for example, e-mail addresses) can be generated using sequences. Sequences are defined by passing Jamaker::sequence() as an attribute value, and values in a sequence are generated by by it:

```php
<?php
Jamaker::factory('user', array(
	'name' => 'A User',
	'email' => Jamaker::sequence(function($n){ return "person{$n}@example.com"; })
));

echo Jamaker::build('user')->email; // 'person1@example.com'
echo Jamaker::build('user')->email; // 'person2@example.com'
?>
```

If you don't pass any callback function, then the sequence will be of just the iterator

```php
<?php
Jamaker::factory('user', array(
	'id' => Jamaker::sequence()
));

echo Jamaker::build('user')->id; // 1
echo Jamaker::build('user')->id; // 2
?>
```

For simpler sequences you could also write just a string containing '$n' at the appropriate place

```php
<?php
Jamaker::factory('user', array(
	'email' => Jamaker::sequence('person$n@example.com')
));
?>
```

If fact, you can make this even more simpler by just passing a string, containing '$n'. Any such string will be converted to a sequence.

```php
<?php
Jamaker::factory('user', array(
	'email' => 'person$n@example.com'
));
?>
```

Another way of defining a sequence is by passing an array. It will loop through that array and get a differnt value each time.

```php
<?php
Jamaker::factory('user', array(
	'email' => Jamaker::sequence(array('person@example.com', 'nemi@example.com', 'colio@example.com'))
));
?>
```

You can also override the initial value:

```php
<?php
Jamaker::factory('user', array(
	'email' => Jamaker::sequence('person$n@example.com', 1000)
));
?>
```

Traits
------

Traits allow you to group attributes together and then apply them to any factory.

```php
<?php

Jamaker::factory('user', array(
	'email' => 'person@example.com'
));

Jamaker::factory('story', array(
	'title' => 'My awesome story',
	'author' => 'user',

	Jamaker::trait('published', array('published' => TRUE)),

	Jamaker::trait('unpublished', array('published' => FALSE)),

	Jamaker::trait('week_long_publishing', array(
		'start_at' => function(){ return strtotime('-1 week')},
		'end_at' => function(){ return strtotime('now')},
	)),

	Jamaker::trait('month_long_publishing', array(
		'start_at' => function(){ return strtotime('-1 month')},
		'end_at' => function(){ return strtotime('now')},
	)),

	Jamaker::factory('week_long_published_story', array('traits' => array('published', 'week_long_publishing')), array()),
	Jamaker::factory('month_long_published_story', array('traits' => array('published', 'month_long_publishing')), array()),
	Jamaker::factory('week_long_unpublished_story', array('traits' => array('unpublished', 'week_long_publishing')), array()),
	Jamaker::factory('month_long_unpublished_story', array('traits' => array('unpublished', 'month_long_publishing')), array()),
));
?>
```

Traits can be used as attributes. Any value that does not have a key is considered a trait.

```php
<?php
Jamaker::factory('week_long_published_story_with_title', array('parent' => 'story'), array(
	'published',
	'week_long_publishing',
	'title' => function($attrs){ return "Publishing that was started at ".$attrs['start_at']; }
));
?>
```

The trait that defines the attribute latest gets precedence.

```php
<?php
Jamaker::factory('user', array(
	'name' => 'Friendly User',
	'login' => function($attrs){ return $attrs['name']; },

	Jamaker::trait('male', array(
		'name' => 'John Doe',
		'gender' => 'Male',
		'login' => function($attrs){ return $attrs['name'].' (M)'; },
	)),

	Jamaker::trait('female', array(
		'name' => 'Jane Doe',
		'gender' => 'Female',
		'login' => function($attrs){ return $attrs['name'].' (F)'; },
	)),

	Jamaker::trait('admin', array(
		'admin' => TRUE,
		'login' => function($attrs){ return 'admin-'.$attrs['name']; },
	)),

	// login will be "admin-John Doe"
	Jamaker::factory('male_admin', array('traits' => array('male', 'admin')), array()),

	// login will be "Jane Doe (F)"  
	Jamaker::factory('female_admin', array('traits' => array('admin', 'female')), array()),
));
?>
```

You can also override individual attributes granted by a trait in subclasses.

```php
<?php
Jamaker::factory('user', array(
	'name' => 'Friendly User',
	'login' => function($attrs){ return $attrs['name']; },

	Jamaker::trait('male', array(
		'name' => 'John Doe',
		'gender' => 'Male',
		'login' => function($attrs){ return $attrs['name'].' (M)'; },
	)),

	Jamaker::factory('brandon'), array(
		'male',
		'name' => 'Brandon'
	)),
));
?>
```

Traits can also be passed in as overrides when you build the instance.


```php
<?php
Jamaker::factory('user', array(
	'name' => 'Friendly User',

	Jamaker::trait('male', array(
		'name' => 'John Doe',
		'gender' => 'Male',
	)),

	Jamaker::trait('admin', array(
		'admin' => TRUE,
	)),
));

Jamaker::create('user', array('male', 'admin'));
?>
```

This ability works with `build`, `create` and `attributes_for`.

`create_list` and `build_list` methods are supported as well. Just remember to pass the number of instances to create/build as second parameter, as documented in the "Building or Creating Multiple Records" section of this file.


```php
<?php
Jamaker::factory('user', array(
	'name' => 'Friendly User',

	Jamaker::trait('admin', array(
		'admin' => TRUE,
	)),
));

Jamaker::create_list('user', 3, array('male', 'admin'));
?>
```

Callbacks
---------

Jamaker makes available three callbacks for injecting some code:

* Jamaker::after('build')   - called after a factory is built   (via `Jamaker::build`, `Jamaker::create`)
* Jamaker::before('create') - called before a factory is saved  (via `Jamaker::create`)
* Jamaker::after('create')  - called after a factory is saved   (via `Jamaker::create`)

Examples:

```php
<?php
Jamaker::factory('user', array(
	Jamaker::after('build', function($user){ Model_User::generate_hashed_password($user); })
));
?>
```

Note that you'll have an instance of the user in the function. This can be useful.

You can also define multiple types of callbacks on the same factory:

```php
<?php
Jamaker::factory('user', array(
	Jamaker::after('build', function($user){ Model_User::do_something_to($user); }
	Jamaker::after('create', function($user){ Model_User::do_something_else_to($user); }
));
?>
```

Factories can also define any number of the same kind of callback.  These callbacks will be executed in the order they are specified:

```php
<?php
Jamaker::factory('user', array(
	Jamaker::after('create', function(){ this_runs_first() }
	Jamaker::after('create', function(){ then_this() }
));
?>
```

Calling Jamaker::create will invoke both `build.after` and `create.after` callbacks.

Also, like standard attributes, child factories will inherit (and can also define) callbacks from their parent factory.


Building or Creating Multiple Records
-------------------------------------

Sometimes, you'll want to create or build multiple instances of a factory at once.

```php
<?php
$built_users   = Jamaker::build_list('user', 25);
$created_users = Jamaker::build_list('user', 25);
?>
```

These methods will build or create a specific amount of factories and return them as an array.
To set the attributes for each of the factories, you can pass in a hash as you normally would.

```php
<?php
$twenty_year_olds = Jamaker::build_list('user', 25, array('date_of_birth' => strtotime('-20 years')));
?>
```
