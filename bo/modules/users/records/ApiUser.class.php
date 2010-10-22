<?php

require_once ('Affiliate.class.php');

class ApiUser extends Record {
	protected $database = DBs::HNSDEV;
	protected $tableName = 'usr_users';

	protected $config = array(
		'id'			=> array(),
		'name' 			=> array(),
		'key' 			=> array(),
		'ip' 			=> array(),
		'contact'		=> array(),
		'email' 		=> array(),
		'phone_number'	=> array(),
		'affiliate_id'	=> array(),
		'max_rate'		=> array(),
		'max_load'		=> array(),
	);
	
	protected $hasOneConfig = array(
		'affiliate' => array(
			'class' => 'Affiliate',
			'local' => 'affiliate_id',
			'foreign' => 'id',
		),
	);
	
}