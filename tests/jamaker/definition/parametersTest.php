<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.definition
 * @group   jamaker.definition.parameters
 */
class Maker_Definition_ParametersTest extends Unittest_Maker_TestCase {


	public function test_definition_parameters()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John',

			Jamaker::trait('admin', array('admin' => TRUE)),
		));

		Jamaker::define('admin', array(
			'model' => 'jamaker_user', 
			'parent' => 'jamaker_user', 
			'traits' => 'admin'
		), array(
			'last_name' => 'Administrator'
		));

		$maker = Jamaker::get('admin');

		$this->assertEquals('jamaker_user', $maker->model(), 'Should have the proper model');
		$this->assertEquals('jamaker_user', $maker->parent(), 'Should have the proper parent parameter');
		$this->assertEquals(array('admin'), $maker->traits(), 'Should have the proper traits parameter');
	}

	public function test_parameters_nested()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John',
			'last_name' => 'Doe',

			Jamaker::define('admin', array(
				'admin' => TRUE
			)),

			Jamaker::define('admin2', array(
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
		$admin_maker = Jamaker::define('admin', array('parent' => 'jamaker_user'), array(
			'admin' => TRUE
		));

		$user_maker = Jamaker::define('jamaker_user', array(
			'first_name' => 'John',
			'last_name' => 'Doe',
		));

		$user = Jamaker::build('jamaker_user');
		$admin = Jamaker::build('admin');

		$this->assertAttributes(array('admin' => FALSE), $user);

		$this->assertEquals('jamaker_user', $admin_maker->model(), 'Should get model from parent definition');
		$this->assertAttributes(array('admin' => TRUE), $admin);
	}

	public function test_definition_traits()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John1',

			Jamaker::trait('admin', array('admin' => TRUE)),
			Jamaker::trait('family', array('last_name' => 'Soprano')),
			
			Jamaker::define('admin_user', array('traits' => 'admin'), array(
				'first_name' => 'John2'
			)),

			Jamaker::define('family_user', array('traits' => 'family'), array(
				'first_name' => 'John3'
			)),

			Jamaker::define('family_user_short', array(
				'family',
				'first_name' => 'John3'
			)),

			Jamaker::define('admin_family_user', array('traits' => array('admin', 'family')), array(
				'first_name' => 'John4'
			)),

			Jamaker::define('admin_family_user_short', array(
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


		// $this->assertAttributes(array('first_name' => 'John1', 'admin' => FALSE, 'last_name' => ''), $user);
		$this->assertAttributes(array('first_name' => 'John2', 'admin' => TRUE, 'last_name' => ''), $admin_user);
		$this->assertAttributes(array('first_name' => 'John3', 'admin' => FALSE, 'last_name' => 'Soprano'), $family_user);
		$this->assertAttributes(array('first_name' => 'John3', 'admin' => FALSE, 'last_name' => 'Soprano'), $family_user_short);
		$this->assertAttributes(array('first_name' => 'John4', 'admin' => TRUE, 'last_name' => 'Soprano'), $admin_family_user);
		$this->assertAttributes(array('first_name' => 'John4', 'admin' => TRUE, 'last_name' => 'Soprano'), $admin_family_user_short);
	}

	public function test_definition_callbacks()
	{
		$param = 0;

		Jamaker::define('jamaker_user', array(
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

