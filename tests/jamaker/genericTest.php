<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.generic
 */
class Jamaker_genericTest extends Unittest_Jamaker_TestCase {

	public function test_generic()
	{
		Jamaker::define('generic_user', array(
			'_accounts' => 3,
			'first_name' => 'John',
			'admin',

			Jamaker::trait('admin', array('admin' => TRUE)),

			Jamaker::after('build', function($user){ 
				$user->accounts = Jamaker::build_list('generic_account', $user->_accounts, array('test' => 'override_test'));
			})
		));

		Jamaker::define('generic_account', array(
			'total' => 10
		));

		$user = Jamaker::build('generic_user');

		$this->assertCount(3, $user->accounts, 'should build 3 accounts by default');
		$this->assertEquals(TRUE, $user->admin);

		foreach ($user->accounts as $account) 
		{
			$this->assertInstanceOf('stdClass', $account);
			$this->assertEquals(10, $account->total);
			$this->assertEquals('override_test', $account->test);
		}

	}

	
}

