<?php defined('SYSPATH') OR die('No direct script access.');


/**
 * Unittest Extension to work agianst the testing database
 */
class Unittest_Jamaker_TestCase extends Unittest_TestCase {

	static public $database_connection = 30;
	public $defined = array();
	public $cleaner = TRUE;

	public function setUp()
	{
		parent::setUp();

		if ($this->cleaner)
		{
			Jamaker_Cleaner::start(Jamaker_Cleaner::TRUNCATE, Unittest_Jamaker_TestCase::$database_connection);
		}

		$this->defined = array_keys(Jamaker::factories());
	}

	public function tearDown()
	{
		$defined = array_diff(array_keys(Jamaker::factories()), $this->defined);
		Jamaker::clear_factories($defined);
		
		if ($this->cleaner)
		{
			Jamaker_Cleaner::clean();
		}
	}

	public function assertAttributes($attributes, $item, $message = 'Should match attributes')
	{
		$this->assertEquals($attributes, Arr::extract($item->as_array(), array_keys($attributes)), $message);
	}
}