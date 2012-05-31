<?php defined('SYSPATH') OR die('No direct access allowed.'); 

class Model_Jamaker_User extends Jam_Model {

	static public function initialize(Jam_Meta $meta)
	{
		$meta->db(Unittest_Jamaker_TestCase::$database_connection);

		$meta->associations(array(
			'invite' => Jam::association('hasone', array('foreign' => 'jamaker_invite.user_id', 'inverse_of' => 'user')),
			'accounts' => Jam::association('hasmany', array('foreign' => 'jamaker_account.user_id', 'inverse_of' => 'user'))
		));

		$meta->fields(array(
			'id' => Jam::field('primary'),
			'email' => Jam::field('email'),
			'first_name' => Jam::field('string'),
			'last_name' => Jam::field('string'),
			'admin' => Jam::field('boolean'),
			'username' => Jam::field('string'),
		));
	}
}