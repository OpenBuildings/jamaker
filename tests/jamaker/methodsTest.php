<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.methods
 */
class Jamaker_MethodsTest extends Unittest_Jamaker_TestCase {

	public function test_duplicate_definition()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John', 
			'last_name' => 'Doe',
		));

		$this->setExpectedException('Kohana_Exception', 'Jamaker jamaker_user already defined');
		
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John', 
			'last_name' => 'Doe',
		));
	}

	public function test_build()
	{
		$called_methods = array();
		Jamaker::define('jamaker_user', array(
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

		$this->assertInstanceOf('Model_Jamaker_User', $user, 'Should build the right Jam_Model object');
		$this->assertFalse($user->loaded(), 'User should not be loaded with build');
		$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Oliver', 'email' => 'test@example.com'), $user, 'Should set attributes for build, and use overrides');

		$this->assertContains('build.after', $called_methods, 'Should call after build callback for build');
		$this->assertNotContains('create.before', $called_methods, 'Should not call before create callback for build');
		$this->assertNotContains('create.after', $called_methods, 'Should call after create callback for build');
	}

	public function test_create()
	{
		$called_methods = array();
		Jamaker::define('jamaker_user', array(
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

		$this->assertInstanceOf('Model_Jamaker_User', $user, 'Should build the right Jam_Model object');
		$this->assertTrue($user->loaded(), 'User should be loaded with build');
		$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Oliver', 'email' => 'test@example.com'), $user, 'Should set attributes for build, and use overrides');

		$this->assertContains('build.after', $called_methods, 'Should call after build callback for create');
		$this->assertContains('create.before', $called_methods, 'Should call before create callback for create');
		$this->assertContains('create.after', $called_methods, 'Should after create callback for create');	
	}

	public function test_build_list()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John', 
			'last_name' => 'Doe',
			'username' => 'doe',
		));

		$usernames = array('username1', 'username2', 'username3', 'username4', 'username5', 'username6', 'username7', 'username8', 'username9', 'username10');

		$users = Jamaker::build_list('jamaker_user', 10, array('email' => 'test@example.com', 'last_name' => 'Oliver', 'username' => $usernames));

		$this->assertCount(10, $users, 'Should build 10 users objects');

		foreach ($users as $i => $user) 
		{
			$this->assertInstanceOf('Model_Jamaker_User', $user, 'Should build the right Jam_Model object');
			$this->assertFalse($user->loaded(), 'User should be loaded with build_list');
			$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Oliver', 'email' => 'test@example.com', 'username' => $usernames[$i]), $user, 'Should set attributes for build_list, and use overrides');
		}
	}


	public function test_create_list()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John', 
			'last_name' => 'Doe',
		));

		$users = Jamaker::create_list('jamaker_user', 10, array('email' => 'test@example.com', 'last_name' => 'Oliver'));

		$this->assertCount(10, $users, 'Should build 10 users objects');

		foreach ($users as $user) 
		{
			$this->assertInstanceOf('Model_Jamaker_User', $user, 'Should build the right Jam_Model object');
			$this->assertTrue($user->loaded(), 'User should be loaded with build_list');
			$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Oliver', 'email' => 'test@example.com'), $user, 'Should set attributes for build_list, and use overrides');
		}
	}

	public function test_lorem()
	{
		$text = Jamaker::lorem(1000);
		$this->assertInternalType('string', $text, 'Should generate a string');

		$this->assertEquals(1000, strlen($text), 'Should generate string of 1000 chars');
		$this->assertGreaterThan(1, substr_count($text, 'Lorem ipsum'), 'Should have repeating block of text');
	}

	
}

