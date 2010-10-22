<?php

class Developer extends Record {
	protected $tableName = 'developer';
	protected $softKeyDefinition = "usualname||' '||surname";

	protected $config = array(
		'initials' => array('type' => self::STRING, 'length' => 10),
		'usualname' => array('type' => self::STRING, 'length' => 25),
		'surname' => array('type' => self::STRING, 'length' => 50),
		'gender' => array('type' => self::INT),
		'date_birth' => array('type' => self::DATE),
		'nationality' => array('type' => self::LOOKUP, 'lookup' => 'sys_nationalities'),
		'picture' => array('type' => self::STRING, 'length' => 250),
		'party' => array('type' => self::INT),
		'address' => array('type' => self::STRING, 'length' => 50),
		'workphone' => array('type' => self::STRING, 'length' => 15),
		'mobilephone' => array('type' => self::STRING, 'length' => 15),
		'password' => array('type' => self::STRING, 'length' => 40),
		'created' => array('type' => self::DATE),
		'email' => array('type' => self::STRING, 'length' => 50, 'regex' => '!^.+@.+\..+$!'),
	);
}

?>