<?php

class HnsDevUser extends Record {
	protected $tableName = 'usr_users';

	protected $config = array(
		'id' => array(),
		'name' => array(),
		'key' => array(),
		'ip' => array(),
		'contact' => array(),
		'email' => array(),
		'phone_number' => array(),
		'affiliate_id' => array(),
		'max_rate' => array(),
		'max_load' => array(),
	);
	
	public function __construct() {
		parent::__construct();
		$this->db = DBs::inst(DBs::HNSDEV);
	}
}

?>