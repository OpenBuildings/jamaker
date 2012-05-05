<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.definition
 * @group   jamaker.definition.attributes
 */
class Maker_Definition_AttributesTest extends Unittest_Maker_TestCase {

	public function provider_static()
	{
		return array(
			array(array('first_name' => 'John', 'last_name' => 'Doe', 'admin' => TRUE)),
			array(array('first_name' => 'Michael', 'last_name' => 'Smith', 'admin' => FALSE)),
			array(array('first_name' => 'Alex', 'last_name' => 'Hill', 'admin' => TRUE)),
		);
	}

	/**
	 * @dataProvider provider_static
	 */
	public function test_static($attributes)
	{
		Jamaker::define('jamaker_user', $attributes);

		$user = Jamaker::build('jamaker_user', array('email' => 'test@example.com'));

		$this->assertInstanceOf('Model_Jamaker_User', $user, 'Should build the right Jelly_Model object');
		$this->assertAttributes($attributes, $user);
		$this->assertEquals('test@example.com', $user->email, 'Should use overrides');
	}

	public function test_sequence()
	{
		$user_maker = Jamaker::define('jamaker_user', array(
			'id' => Jamaker::sequence(),
			'first_name' => Jamaker::sequence('Name$n'),
			'last_name' => Jamaker::sequence(array('Fam1', 'Fam2', 'Fam3')),
			'email' => Jamaker::sequence(function($n){ return 'me'.$n.'@example.com'; }),
			'admin' => function($n){ return $n % 2; },
			'username' => 'user-$n',
		));

		$user_maker = array(
			Jamaker::build('jamaker_user'),
			Jamaker::build('jamaker_user'),
			Jamaker::build('jamaker_user'),
			Jamaker::build('jamaker_user')
		);

		$this->assertAttributes(array(
			'id' => 1, 
			'first_name' => 'Name1', 
			'last_name' => 'Fam1', 
			'email' => 'me1@example.com', 
			'username' => 'user-1', 
			'admin' => TRUE
		), $user_maker[0]);

		$this->assertAttributes(array(
			'id' => 2, 
			'first_name' => 'Name2', 
			'last_name' => 'Fam2', 
			'email' => 'me2@example.com', 
			'username' => 'user-2', 
			'admin' => FALSE
		), $user_maker[1]);

		$this->assertAttributes(array(
			'id' => 3, 
			'first_name' => 'Name3', 
			'last_name' => 'Fam3', 
			'email' => 'me3@example.com', 
			'username' => 'user-3', 
			'admin' => TRUE
		), $user_maker[2]);

		$this->assertAttributes(array(
			'id' => 4, 
			'first_name' => 'Name4', 
			'last_name' => 'Fam1', 
			'email' => 'me4@example.com', 
			'username' => 'user-4', 
			'admin' => FALSE
		), $user_maker[3]);

	}

	public function test_association_belongsto()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John',
			'last_name' => 'Doe',
		));

		Jamaker::define('jamaker_invite', array(
			'user' => 'jamaker_user',
		));

		Jamaker::define('jamaker_account', array(
			'user' => Jamaker::association('jamaker_user', array('strategy' => 'create'))
		));

		$invite = Jamaker::build('jamaker_invite');
		$this->assertFalse($invite->user->loaded(), 'Should use build strategy by default');

		$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Doe'), $invite->user);

		$account = Jamaker::build('jamaker_account');

		$this->assertTrue($account->user->loaded(), 'Should use create strategy as specified');
		$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Doe'), $account->user);
	}

	public function test_association_hasone()
	{
		Jamaker::define('jamaker_user', array(
			'first_name' => 'John',
			'invite' => 'jamaker_invite'
		));

		Jamaker::define('jamaker_invite', array(
			'email' => 'me@example.com',
		));

		$user = Jamaker::build('jamaker_user');

		$this->assertAttributes(array('email' => 'me@example.com'), $user->invite);
	}

	public function test_association_hamany()
	{
		Jamaker::define('jamaker_user', array(
			'_accounts' => 3,
			'first_name' => 'John',
			Jamaker::after('build', function($user){
				$user->accounts = Jamaker::build_list('jamaker_account', $user->_accounts);
			})
		));

		Jamaker::define('jamaker_account', array());

		$user = Jamaker::build('jamaker_user');
		$user2 = Jamaker::build('jamaker_user', array('_accounts' => 10));

		$this->assertCount(3, $user->accounts, 'should build 3 accounts by default');
		$this->assertCount(10, $user2->accounts, 'should build 10 accounts as specified');

		foreach ($user->accounts as $account) 
		{
			$this->assertSame($user, $account->user, 'Should assign user to collection');
			$this->assertInstanceOf('Model_Jamaker_Account', $account);
		}

		foreach ($user2->accounts as $account) 
		{
			$this->assertSame($user2, $account->user, 'Should assign user to collection');
			$this->assertInstanceOf('Model_Jamaker_Account', $account);
		}
	}

}

