<?php defined('SYSPATH') OR die('No direct access allowed.'); 

class Model_Jamaker_User extends Jelly_Model {

	static public function initialize(Jelly_Meta $meta)
	{
		$meta->db(Unittest_Jamaker_TestCase::$database_connection);

		$meta->associations(array(
			'invite' => Jelly::association('hasone', array('foreign' => 'jamaker_invite.user_id', 'inverse_of' => 'user')),
			'accounts' => Jelly::association('hasmany', array('foreign' => 'jamaker_account.user_id', 'inverse_of' => 'user'))
		));

		$meta->fields(array(
			'id' => Jelly::field('primary'),
			'email' => Jelly::field('email'),
			'first_name' => Jelly::field('string'),
			'last_name' => Jelly::field('string'),
			'admin' => Jelly::field('boolean'),
			'username' => Jelly::field('string'),
		));
	}
}