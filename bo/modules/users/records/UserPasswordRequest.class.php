<?php

require_once 'User.class.php';

class UserPasswordRequest extends Record {
	protected $tableName = 'usr_password_requests';

	protected $config = array(
		'id' => '',
		'user_id' => '',
		'hash' => '',
		'created' => array(
			'writability' => self::NONWRITTEN,
		),
		'updated' => array(
			'writability' => self::NONWRITTEN,
		),
	);

	protected $hasOneConfig = array(
		'user' => array(
			'class' => 'User',
			'local' => 'user_id',
			'foreign' => 'id',
		),
	);

	public function loadByHash($hash) {
		$this->loadByUnique('hash', $hash);
	}

	public function generateHash() {
		$this->hash = $this->randomString(40);
	}

	public static function randomString($length) {
		$chars = '';
		for ($i = 'a'; $i < 'z'; $i++) $chars .= $i;
		for ($i = 'A'; $i < 'Z'; $i++) $chars .= $i;
		for ($i = '0'; $i < '9'; $i++) $chars .= $i;

		$result = '';
		for ($i = 0; $i < $length; $i++)
			$result .= $chars[mt_rand(0, strlen($chars) - 1)];

		return $result;
	}
}

?>