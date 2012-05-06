<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.definition
 * @group   jamaker.definition.parameters
 */
class Jamaker_Definition_ParametersTest extends Unittest_Maker_TestCase {


	public function test_definition_parameters()
	{
		Jamaker::factory('jamaker_user', array(
			'first_name' => 'John',

			Jamaker::trait('admin', array('admin' => TRUE)),
		));

		Jamaker::factory('admin', array(
			'class' => 'Model_Jamaker_User', 
			'parent' => 'jamaker_user', 
			'traits' => 'admin'
		), array(
			'last_name' => 'Administrator'
		));

		$maker = Jamaker::get('admin');

		$this->assertEquals('model_jamaker_user', $maker->item_class(), 'Should have the proper class');
		$this->assertEquals('jamaker_user', $maker->parent(), 'Should have the proper parent parameter');
		$this->assertArrayHasKey('admin', $maker->traits(), 'Should have the proper traits parameter');
	}

	public function test_parameters_nested()
	{
		Jamaker::factory('jamaker_user', array(
			'first_name' => 'John',
			'last_name' => 'Doe',

			Jamaker::factory('admin', array(
				'admin' => TRUE
			)),

			Jamaker::factory('admin2', array(
				'admin' => TRUE,
				'email' => 'admin@example.com'
			))
		));

		$user = Jamaker::build('jamaker_user');
		$admin = Jamaker::build('admin');
		$admin2 = Jamaker::build('admin2');

		$this->assertAttributes(array('admin' => FALSE), $user);

		$this->assertInstanceOf('Model_Jamaker_User', $admin);
		$this->assertAttributes(array('admin' => TRUE), $admin);

		$this->assertInstanceOf('Model_Jamaker_User', $admin2);
		$this->assertAttributes(array('admin' => TRUE, 'email' => 'admin@example.com'), $admin2);
	}

	public function test_parameters_lazy_loading()
	{
		$admin_maker = Jamaker::factory('admin', array('parent' => 'jamaker_user'), array(
			'admin' => TRUE
		));

		$user_maker = Jamaker::factory('jamaker_user', array(
			'first_name' => 'John',
			'last_name' => 'Doe',
		));

		$user = Jamaker::build('jamaker_user');
		$admin = Jamaker::build('admin');

		$this->assertAttributes(array('admin' => FALSE), $user);

		$this->assertEquals('model_jamaker_user', $admin_maker->item_class(), 'Should get class from parent definition');
		$this->assertAttributes(array('admin' => TRUE), $admin);
	}

	public function test_definition_traits()
	{
		Jamaker::factory('jamaker_user', array(
			'first_name' => 'John1',

			Jamaker::trait('admin', array('admin' => TRUE)),
			Jamaker::trait('family', array('last_name' => 'Soprano')),
			
			Jamaker::factory('admin_user', array('traits' => 'admin'), array(
				'first_name' => 'John2'
			)),

			Jamaker::factory('family_user', array('traits' => 'family'), array(
				'first_name' => 'John3'
			)),

			Jamaker::factory('family_user_short', array(
				'family',
				'first_name' => 'John3'
			)),

			Jamaker::factory('admin_family_user', array('traits' => array('admin', 'family')), array(
				'first_name' => 'John4'
			)),

			Jamaker::factory('admin_family_user_short', array(
				'admin',
				'family',
				'first_name' => 'John4'
			))
		));

		// $user = Jamaker::build('jamaker_user');
		$admin_user = Jamaker::build('admin_user');
		$family_user = Jamaker::build('family_user');
		$family_user_short = Jamaker::build('family_user_short');
		$admin_family_user = Jamaker::build('admin_family_user');
		$admin_family_user_short = Jamaker::build('admin_family_user_short');


		// Mix them up on the spot
		$mixed = Jamaker::build('jamaker_user', array('admin', 'family'));

		// Mix with override
		$mixed_overridden = Jamaker::build('jamaker_user', array('admin', 'family', 'last_name' => 'Green'));


		// $this->assertAttributes(array('first_name' => 'John1', 'admin' => FALSE, 'last_name' => ''), $user);
		$this->assertAttributes(array('first_name' => 'John2', 'admin' => TRUE, 'last_name' => ''), $admin_user);
		$this->assertAttributes(array('first_name' => 'John3', 'admin' => FALSE, 'last_name' => 'Soprano'), $family_user);
		$this->assertAttributes(array('first_name' => 'John3', 'admin' => FALSE, 'last_name' => 'Soprano'), $family_user_short);
		$this->assertAttributes(array('first_name' => 'John4', 'admin' => TRUE, 'last_name' => 'Soprano'), $admin_family_user);
		$this->assertAttributes(array('first_name' => 'John4', 'admin' => TRUE, 'last_name' => 'Soprano'), $admin_family_user_short);
		$this->assertAttributes(array('first_name' => 'John1', 'admin' => TRUE, 'last_name' => 'Soprano'), $mixed);
		$this->assertAttributes(array('first_name' => 'John1', 'admin' => TRUE, 'last_name' => 'Green'), $mixed_overridden);

		// Should not be able to use undefined exceptions
		$this->setExpectedException('Kohana_Exception', 'A trait with the name "undefined_trait" does not exist for factory "jamaker_user"');
		$mixed_wrong = Jamaker::build('jamaker_user', array('undefined_trait', 'family', 'last_name' => 'Green'));
	}

	public function test_trait_precedence()
	{
		Jamaker::factory('jamaker_user', array(
			'first_name' => 'Joe',

			Jamaker::trait('admin', array('first_name' => 'Admin')),

			Jamaker::factory('admin_user', array(
				'first_name' => 'Admin Overwritten',
				'admin',
			)),
			
			Jamaker::factory('admin_john', array(
				'admin',
				'first_name' => 'John Admin'
			)),
		));

		$user = Jamaker::build('jamaker_user');
		$admin = Jamaker::build('admin_user');
		$admin_john = Jamaker::build('admin_john');
		$admin_jane = Jamaker::build('admin_user', array('first_name' => 'Jane Admin'));

		$this->assertAttributes(array('first_name' => 'Joe'), $user);
		$this->assertAttributes(array('first_name' => 'Admin'), $admin);
		$this->assertAttributes(array('first_name' => 'John Admin'), $admin_john);
		$this->assertAttributes(array('first_name' => 'Jane Admin'), $admin_jane);
	}

	public function test_callbacks()
	{
		$param = 0;

		Jamaker::factory('jamaker_user', array(
			'first_name' => 'John',
		
			Jamaker::after('build', function($user) use ( & $param) {
				$param += 1;
			}),

			Jamaker::after('build', function($user) use ( & $param) {
				$param += 3;
			}),

			Jamaker::after('create', function($user) use ( & $param) {
				$param += 100;
			})

		));

		Jamaker::build('jamaker_user');

		$this->assertEquals(4, $param, 'Should run 2 build callbacks after build');

		Jamaker::create('jamaker_user');

		$this->assertEquals(108, $param, 'Should run 2 build callbacks after build and one create callback after create');
	}
}

