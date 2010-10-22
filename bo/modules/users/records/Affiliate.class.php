<?php

class Affiliate extends Record {
	protected $database = DBs::HNSDEV;
	protected $tableName = 'usr_affiliates';

	protected $config = array(
		'id'			=> array(),
		'name' 			=> array(),
		'contact'		=> array(),
		'email' 		=> array(),
		'phone_number'	=> array(),
	);
}