<?php defined('SYSPATH') OR die('No direct access allowed.'); 

class Model_Jamaker_Invite extends Jam_Model {

	static public function initialize(Jam_Meta $meta)
	{
		$meta->db(Unittest_Jamaker_TestCase::$database_connection);

		$meta->associations(array(
			'user' => Jam::association('belongsto', array('foreign' => 'jamaker_user', 'inverse_of' => 'invite', 'column' => 'user_id'))
		));

		$meta->fields(array(
			'id' => Jam::field('primary'),
			'email' => Jam::field('email'),
			'approved_at' => Jam::field('timestamp'),
		));
	}
}