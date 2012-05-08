<?php defined('SYSPATH') OR die('No direct script access.');


/**
 * Unittest Extension to work agianst the testing database
 */
class Unittest_Jamaker_TestCase extends Unittest_Database_TestCase {

	static public $database_connection = 30;
	static public $defined = array();

	public function setUp()
	{
		$this->_database_connection = Unittest_Jamaker_TestCase::$database_connection;
		parent::setUp();

		Unittest_Jamaker_TestCase::$defined = array_keys(Jamaker::facotries());
	}

	public function tearDown()
	{
		Jamaker::clear_created();
		$defined = array_diff(array_keys(Jamaker::facotries()), Unittest_Jamaker_TestCase::$defined);
		Jamaker::clear_factories($defined);
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