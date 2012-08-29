<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.definition
 * @group   jamaker.definition.attributes
 */
class Jamaker_Definition_AttributesTest extends Unittest_Jamaker_TestCase {

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

		$this->assertInstanceOf('Model_Jamaker_User', $user, 'Should build the right Jam_Model object');
		$this->assertAttributes($attributes, $user);
		$this->assertEquals('test@example.com', $user->email, 'Should use overrides');
	}

	public function test_sequence()
	{
		$user_maker = Jamaker::define('jamaker_user', array(
			// Sequence only iterator number
			'id' => Jamaker::sequence(),

			// Sequence with initial parameter
			'first_name' => Jamaker::sequence('Name$n', 10),

			// Sequence of arrays, will loop through them continuously
			'last_name' => Jamaker::sequence(array('Fam1', 'Fam2', 'Fam3')),

			// Sequence with a callback
			'email' => Jamaker::sequence(function($n){ return 'me'.$n.'@example.com'; }),

			// Shorthand string sequence
			'username' => 'user-$n',

			'admin' => array(TRUE, FALSE)
		));

		$user_maker = array(
			Jamaker::build('jamaker_user'),
			Jamaker::build('jamaker_user'),
			Jamaker::build('jamaker_user'),
			Jamaker::build('jamaker_user')
		);

		$this->assertAttributes(array(
			'id' => 1, 
			'first_name' => 'Name10', 
			'last_name' => 'Fam1', 
			'email' => 'me1@example.com', 
			'username' => 'user-1', 
			'admin' => TRUE, 
		), $user_maker[0]);

		$this->assertAttributes(array(
			'id' => 2, 
			'first_name' => 'Name11', 
			'last_name' => 'Fam2', 
			'email' => 'me2@example.com', 
			'username' => 'user-2', 
			'admin' => FALSE, 
		), $user_maker[1]);

		$this->assertAttributes(array(
			'id' => 3, 
			'first_name' => 'Name12', 
			'last_name' => 'Fam3', 
			'email' => 'me3@example.com', 
			'username' => 'user-3', 
			'admin' => TRUE, 
		), $user_maker[2]);

		$this->assertAttributes(array(
			'id' => 4, 
			'first_name' => 'Name13', 
			'last_name' => 'Fam1', 
			'email' => 'me4@example.com', 
			'username' => 'user-4', 
			'admin' => FALSE, 
		), $user_maker[3]);

	}

	public function test_dynamic()
	{
		Jamaker::define('jamaker_user', array(
			'id' => 10,
			'first_name' => function($attrs){ return $attrs['id'].' clusure'; },
			'last_name' => 'Jamaker_Definition_AttributesTest::_test_dynamic_call',
		));

		$user = Jamaker::build('jamaker_user');
		$this->assertAttributes(array('id' => 10, 'first_name' => '10 clusure', 'last_name' => '10 method 10 clusure' ), $user);
	}

	static public function _test_dynamic_call($attrs)
	{
		return $attrs['id'].' method '.$attrs['first_name'];
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
			'user' => Jamaker::association('jamaker_user', array('last_name' => 'Dimo'), 'create')
		));

		$jamaker_user = Jamaker::build('jamaker_user');

		Jamaker::define('jamaker_account_static', array('class' => 'Model_Jamaker_User'), array(
			'user' => $jamaker_user
		));

		$invite = Jamaker::build('jamaker_invite');
		$this->assertFalse($invite->user->loaded(), 'Should use build strategy for associations');

		$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Doe'), $invite->user);

		$invite = Jamaker::create('jamaker_invite');
		$this->assertTrue($invite->user->loaded(), 'Should use create strategy for associations');

		$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Doe'), $invite->user);

		$account = Jamaker::build('jamaker_account');

		$this->assertTrue($account->user->loaded(), 'Should use create strategy as specified');
		$this->assertAttributes(array('first_name' => 'John', 'last_name' => 'Dimo'), $account->user);

		$account = Jamaker::build('jamaker_account_static');
		$this->assertSame($jamaker_user, $account->user, 'Should use assigned object');
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

