<?php defined('SYSPATH') OR die('No direct access allowed.'); 

class Model_Jamaker_Account extends Jelly_Model {

	static public function initialize(Jelly_Meta $meta)
	{
		$meta->db(Unittest_Jamaker_TestCase::$database_connection);

		$meta->associations(array(
			'user' => Jelly::association('belongsto', array('foreign' => 'jamaker_user', 'inverse_of' => 'accounts', 'column' => 'user_id')),
		));

		$meta->fields(array(
			'id' => Jelly::field('primary')
		));
	}
}