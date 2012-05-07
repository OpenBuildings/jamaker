<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.methods
 */
class Jamaker_MethodsTest extends Unittest_Jamaker_TestCase {

	public function test_duplicate_definition()
	{
		Jamaker::factory('jamaker_user', array(
			'first_name' => 'John', 
			'last_name' => 'Doe',
		));

		$this->setExpectedException('Kohana_Exception', 'Jamaker jamaker_user already defined');
		
		Jamaker::factory('jamaker_user', array(
			'first_name' => 'John', 
			'last_name' => 'Doe',
		));

	}

	public function test_build()
	{
		$called_methods = array();
		Jamaker::factory('jamaker_user', array(
			'first_name' => 'John', 
			'last_name' => 'Doe',

			Jamaker::after('build', function($user) use ( & $called_methods) {
				$called_methods[] = 'build.after';
			}),

			Jamaker::before('create', function($user) use ( & $called_methods) {
				$called_methods[] = 'create.before';
			}),

			Jamaker::after('create', function($user) use ( & $called_methods) {
				$called_methods[] = 'create.after';
			}),
		));

		$user = Jamaker::build('jamaker_user', array('email' => 'test@example.com', 'last_name' => 'Oliver'));

		$this->assertInstanceOf('Model_Jamaker_User', $user, 'Should build the right Jelly_Model object');
		$this->assertFalse($user->loaded(), 'User should not be loaded with build');
		$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Oliver', 'email' => 'test@example.com'), $user, 'Should set attributes for build, and use overrides');

		$this->assertContains('build.after', $called_methods, 'Should call after build callback for build');
		$this->assertNotContains('create.before', $called_methods, 'Should not call before create callback for build');
		$this->assertNotContains('create.after', $called_methods, 'Should call after create callback for build');
	}

	public function test_create()
	{
		$called_methods = array();
		Jamaker::factory('jamaker_user', array(
			'first_name' => 'John', 
			'last_name' => 'Doe',

			Jamaker::after('build', function($user) use ( & $called_methods) {
				$called_methods[] = 'build.after';
			}),

			Jamaker::before('create', function($user) use ( & $called_methods) {
				$called_methods[] = 'create.before';
			}),

			Jamaker::after('create', function($user) use ( & $called_methods) {
				$called_methods[] = 'create.after';
			}),
		));

		$user = Jamaker::create('jamaker_user', array('email' => 'test@example.com', 'last_name' => 'Oliver'));

		$this->assertInstanceOf('Model_Jamaker_User', $user, 'Should build the right Jelly_Model object');
		$this->assertTrue($user->loaded(), 'User should be loaded with build');
		$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Oliver', 'email' => 'test@example.com'), $user, 'Should set attributes for build, and use overrides');

		$this->assertContains('build.after', $called_methods, 'Should call after build callback for create');
		$this->assertContains('create.before', $called_methods, 'Should call before create callback for create');
		$this->assertContains('create.after', $called_methods, 'Should after create callback for create');	
	}

	public function test_build_list()
	{
		Jamaker::factory('jamaker_user', array(
			'first_name' => 'John', 
			'last_name' => 'Doe',
		));

		$users = Jamaker::build_list('jamaker_user', 10, array('email' => 'test@example.com', 'last_name' => 'Oliver'));

		$this->assertCount(10, $users, 'Should build 10 users objects');

		foreach ($users as $user) 
		{
			$this->assertInstanceOf('Model_Jamaker_User', $user, 'Should build the right Jelly_Model object');
			$this->assertFalse($user->loaded(), 'User should be loaded with build_list');
			$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Oliver', 'email' => 'test@example.com'), $user, 'Should set attributes for build_list, and use overrides');
		}
	}

	public function test_create_list()
	{
		Jamaker::factory('jamaker_user', array(
			'first_name' => 'John', 
			'last_name' => 'Doe',
		));

		$users = Jamaker::create_list('jamaker_user', 10, array('email' => 'test@example.com', 'last_name' => 'Oliver'));

		$this->assertCount(10, $users, 'Should build 10 users objects');

		foreach ($users as $user) 
		{
			$this->assertInstanceOf('Model_Jamaker_User', $user, 'Should build the right Jelly_Model object');
			$this->assertTrue($user->loaded(), 'User should be loaded with build_list');
			$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Oliver', 'email' => 'test@example.com'), $user, 'Should set attributes for build_list, and use overrides');
		}
	}
}

