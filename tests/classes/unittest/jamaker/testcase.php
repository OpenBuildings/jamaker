<?php defined('SYSPATH') OR die('No direct script access.');


/**
 * Unittest Extension to work agianst the testing database
 */
class Unittest_Maker_TestCase extends Unittest_Database_TestCase {

	static public $database_connection = 30;

	public function setUp()
	{
		$this->_database_connection = Unittest_Maker_Testcase::$database_connection;
		parent::setUp();

		Jamaker::clear_definitions();
	}

	public function tearDown()
	{
		Jamaker::clear_created();
	}

	public function getDataSet()
	{
		return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
	}

	public function assertAttributes($attributes, $item, $message = 'Should match attributes')
	{
		$this->assertEquals($attributes, Arr::extract($item->as_array(), array_keys($attributes)), $message);
	}
}