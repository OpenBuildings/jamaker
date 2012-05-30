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

	public function test_trait_callback()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John',

			Jamaker::trait('test', array(
				Jamaker::after('build', function($user) {
					$user->first_name = 'Joe';
				}),
			))
		));

		$user = Jamaker::build('jamaker_user', array('test'));
		$user_no_trait = Jamaker::build('jamaker_user');

		$this->assertAttributes(array('first_name' => 'Joe'), $user);
		$this->assertAttributes(array('first_name' => 'John'), $user_no_trait);

	}

	public function test_multiple_nested_callbacks()
	{
		Jamaker::define('jamaker_image', array(
			'file' => 'file$n.jpg',

			Jamaker::after('build', function($image){
				$image->file = 'account_'.$image->account->name.'.jpg';
			})
		));

		Jamaker::define('jamaker_invite', array(
			'email' => 'invite@example.com',

			Jamaker::after('build', function($invite){
				$invite->email = $invite->user->email;
			})
		));

		Jamaker::define('jamaker_account', array(
			'user' => 'jamaker_normal_user',
			'name' => function($attrs){ return $attrs['user']->first_name.'_account'; },

			Jamaker::trait('images', array(
				'_images' => 3,
				Jamaker::after('build', function($account){
					$account->images = Jamaker::build_list('jamaker_image', $account->_images, array('creator' => $account->user));
				})
			)),

			Jamaker::define('jamaker_account_recursive', array(
				'user' => 'jamaker_recursive_user',
			))
		));

		Jamaker::define('jamaker_user', array(
			'first_name' => 'Joe',
			'last_name' => 'error',

			Jamaker::trait('named', array(
				'last_name' => 'Morgan',

				Jamaker::after('build', function($user){
					$user->username = $user->email.'_username';
				})
			)),

			'email' => function($attrs){ return $attrs['first_name'].$attrs['last_name'].'@example.com'; },
			'invite' => 'jamaker_invite',

			Jamaker::define('jamaker_normal_user', array(
				Jamaker::after('build', function($user) {
					$user->last_name = 'trait last';
					$user->accounts = Jamaker::build_list('jamaker_account', 10, array('images', '_images' => 10, 'user' => $user));
				}),
			)),

			Jamaker::define('jamaker_recursive_user', array(

				// No user set, so will be a recursive definition with jamaker_account
				Jamaker::after('build', function($user) {
					$user->last_name = 'trait last';
					$user->accounts = Jamaker::build_list('jamaker_account_recursive', 10, array('images', '_images' => 10));
				}),
			))
		));

		$user = Jamaker::build('jamaker_normal_user', array('named'));

		$this->assertAttributes(array(
			'first_name' => 'Joe',
			'last_name' => 'trait last',
			'email' => 'JoeMorgan@example.com',
			'username' => 'JoeMorgan@example.com_username',
		), $user);

		$this->assertCount(10, $user->accounts);
		$this->assertSame($user, $user->accounts[1]->images[1]->creator, 'Should be the same object');

		// $this->setExpectedException('Kohana_Exception', 'Recursive definition detected in jamaker_recursive_user');

		// $user = Jamaker::build('jamaker_recursive_user', array('named'));
	}
}

