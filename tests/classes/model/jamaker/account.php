<?php defined('SYSPATH') OR die('No direct access allowed.'); 

class Model_Jamaker_Account extends Jam_Model {

	static public function initialize(Jam_Meta $meta)
	{
		$meta->db(Unittest_Jamaker_TestCase::$database_connection);

		$meta->associations(array(
			'user' => Jam::association('belongsto', array('foreign' => 'jamaker_user', 'inverse_of' => 'accounts', 'column' => 'user_id')),
			'images' => Jam::association('hasmany', array('foreign' => 'jamaker_image.account_id', 'inverse_of' => 'account')),
		));

		$meta->fields(array(
			'id' => Jam::field('primary'),
			'name' => Jam::field('string')
		));
	}
}