<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jamaker
 * @group   jamaker
 * @group   jamaker.autoload
 */
class Jamaker_autoloadTest extends Unittest_Jamaker_TestCase {

	public function test_autoload()
	{
		$this->assertNotNull(Jamaker::factories('test_jamaker_root'), 'Should load jamakers from test/test_data/jamaker.php');
		$this->assertNotNull(Jamaker::factories('test_jamaker_inner'), 'Should load jamakers from test/test_data/jamaker/test.php');
	}
}

