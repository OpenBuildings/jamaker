<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.cleaner
 */
class Jamaker_cleanerTest extends Unittest_Jamaker_TestCase {

	public $cleaner = FALSE;
	public $db;

	public function setUp()
	{
		parent::setUp();
		$this->db = Unittest_Jamaker_TestCase::$database_connection;
		Jamaker_Cleaner::started(FALSE);
	}

	public function provider_strategy()
	{
		return array(
			array(Jamaker_Cleaner::TRANSACTION),
			array(Jamaker_Cleaner::TRUNCATE),
			array(Jamaker_Cleaner::DELETE),
		);
	}

	/**
	 * @dataProvider provider_strategy
	 */
	public function test_strategy($strategy)
	{
		Jamaker_Cleaner::start($strategy, $this->db);
		Jelly::query('jamaker_account')->columns(array('id'))->values(array('1'))->insert();

		$count = Jelly::query('jamaker_account')->count();
		$this->assertEquals(1, $count, 'Should have added one record');

		// Test normal clean
		Jamaker_Cleaner::clean();

		$count = Jelly::query('jamaker_account')->count();
		$this->assertEquals(0, $count, 'Should have cleaned the inserted record');

		// Test persisting records
		Jelly::query('jamaker_account')->columns(array('id'))->values(array('1'))->insert();

		Jamaker_Cleaner::save();
		Jamaker_Cleaner::clean();

		$count = Jelly::query('jamaker_account')->count();
		$this->assertEquals(1, $count, 'Should keep the record in the database after persist');

		Jelly::query('jamaker_account')->delete();
	}

	public function test_clean_without_start()
	{
		$this->setExpectedException('Kohana_Exception', 'Jamaker Cleaner has not been started, plase start it with Jamaker_Cleaner::start()');

		Jamaker_Cleaner::clean();	
	}

	public function test_wrong_strategy()
	{
		$this->setExpectedException('Kohana_Exception');
		Jamaker_Cleaner::start('sdfj', $this->db);
	}

	
}

