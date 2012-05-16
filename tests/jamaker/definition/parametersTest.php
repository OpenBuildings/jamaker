<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.definition
 * @group   jamaker.definition.parameters
 */
class Jamaker_Definition_ParametersTest extends Unittest_Jamaker_TestCase {


	public function test_definition_parameters()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John',

			Jamaker::trait('admin', array('admin' => TRUE)),
		));

		Jamaker::define('admin', array(
			'class' => 'Model_Jamaker_User', 
			'parent' => 'jamaker_user', 
			'traits' => 'admin'
		), array(
			'last_name' => 'Administrator'
		));

		$factory = Jamaker::factories('admin');

		$this->assertEquals('model_jamaker_user', $factory->item_class(), 'Should have the proper class');
		$this->assertEquals('jamaker_user', $factory->parent(), 'Should have the proper parent parameter');
		$this->assertArrayHasKey('admin', $factory->traits(), 'Should have the proper traits parameter');
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
		$admin_factory = Jamaker::define('admin', array('parent' => 'jamaker_user'), array(
			'admin' => TRUE
		));

		$user_factory = Jamaker::define('jamaker_user', array(
			'first_name' => 'John',
			'last_name' => 'Doe',
		));

		$user = Jamaker::build('jamaker_user');
		$admin = Jamaker::build('admin');

		$this->assertAttributes(array('admin' => FALSE), $user);

		$this->assertEquals('model_jamaker_user', $admin_factory->item_class(), 'Should get class from parent definition');
		$this->assertAttributes(array('admin' => TRUE), $admin);
	}


	public function test_callbacks()
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

