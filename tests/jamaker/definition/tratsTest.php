<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.definition
 * @group   jamaker.definition.traits
 */
class Jamaker_Definition_TraitsTest extends Unittest_Jamaker_TestCase {

	public function test_basic()
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

	public function test_precedence()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'Joe',

			Jamaker::trait('admin', array('first_name' => 'Admin')),

			Jamaker::define('admin_user', array(
				'first_name' => 'Admin Overwritten',
				'admin',
			)),
			
			Jamaker::define('admin_john', array(
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

	public function test_shared()
	{
		$shared = Jamaker::trait('email', array('email' => 'shared@example.com'));

		Jamaker::define('jamaker_user', array(
			'first_name' => 'Joe',
			$shared,
			'email',
		));

		Jamaker::define('jamaker_invite', array(
			$shared,
			'email',
		));

		$user = Jamaker::build('jamaker_user');
		$invite = Jamaker::build('jamaker_invite');


		$this->assertAttributes(array('first_name' => 'Joe', 'email' => 'shared@example.com'), $user);
		$this->assertAttributes(array('email' => 'shared@example.com'), $invite);
	}

	public function test_callbacks()
	{
		Jamaker::define('jamaker_invite', array(
			'email' => 'invite@example.com',
		));

		Jamaker::define('jamaker_account', array(
		));

		Jamaker::define('jamaker_user', array(
			'first_name' => 'Joe',
			'email' => 'default@example.com',

			Jamaker::trait('with_callback', array(
				'email' => 'shared@example.com',

				Jamaker::after('build', function($user) {
					$user->last_name = 'trait last';
					$user->invite = Jamaker::build('jamaker_invite');
					$user->accounts = Jamaker::build_list('jamaker_account', 10);
				})
			)),

			Jamaker::define('admin_user', array(
				'with_callback',
				'admin' => TRUE,
			)),
		));

		$user = Jamaker::build('jamaker_user');
		$user_with_trait = Jamaker::build('jamaker_user', array('with_callback'));
		$admin = Jamaker::build('admin_user');

		$this->assertAttributes(array('first_name' => 'Joe', 'email' => 'default@example.com', 'admin' => FALSE), $user);
		$this->assertAttributes(array('first_name' => 'Joe', 'email' => 'shared@example.com', 'admin' => FALSE, 'last_name' => 'trait last'), $user_with_trait);

		$this->assertAttributes(array('email' => 'invite@example.com'), $user_with_trait->invite); 
		$this->assertCount(10, $user_with_trait->accounts); 

		$this->assertAttributes(array('first_name' => 'Joe', 'email' => 'shared@example.com', 'admin' => TRUE, 'last_name' => 'trait last'), $admin);

		$this->assertAttributes(array('email' => 'invite@example.com'), $admin->invite); 
		$this->assertCount(10, $admin->accounts); 
	}
}

