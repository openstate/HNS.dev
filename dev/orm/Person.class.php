<?php

class Person extends Record {
	protected $tableName = 'persons';
	protected $softKeyDefinition = "usualname||' '||lastname";

	protected $config = array(
		'initials' => array('type' => self::STRING, 'length' => 10),
		'usualname' => array('type' => self::STRING, 'length' => 25),
		'lastname' => array('type' => self::STRING, 'length' => 25),
		'gender' => array('type' => self::INT),
		'date_birth' => array('type' => self::DATE),
		'nationality' => array('type' => self::LOOKUP, 'lookup' => 'sys_nationalities'),
		'residency' => array('type' => self::STRING, 'length' => 50),
		'picture' => array('type' => self::STRING, 'length' => 40),
		'address' => array('type' => self::STRING, 'length' => 50),
		'workphone' => array('type' => self::STRING, 'length' => 15),
		'mobilephone' => array('type' => self::STRING, 'length' => 15),
		'rights' => array('type' => self::STRING, 'length' => 50),
		'website' => array('type' => self::STRING, 'length' => 50, 'regex' => '!^https?://!'),
		'blog' => array('type' => self::STRING, 'length' => 50, 'regex' => '!^https?://!'),
		'email' => array('type' => self::STRING, 'length' => 50, 'regex' => '!^.+@.+\..+$!'),
		'bio' => array('type' => self::STRING, 'length' => 50),
		'place_birth' => array('type' => self::STRING, 'length' => 50),
		'country_birth' => array('type' => self::STRING),
		'origin_mom' => array('type' => self::LOOKUP, 'lookup' => 'sys_nationalities'),
		'origin_dad' => array('type' => self::LOOKUP, 'lookup' => 'sys_nationalities'),
		'marital_status' => array('type' => self::INT),
	);
	
	protected $hasManyConfig = array(
		'citations' => array(
			'class' 	=> 'Citation',
			'foreign'	=> 'person',
		),
		'cited_documents' => array(
			'class' 	=> 'Document',
			'table' 	=> array(
				'class'		=> 'Citation',
				'name'		=> 'citations',
				'local'		=> 'person',
				'foreign'	=> 'document',
			),
		),
		'functions' => array(
			'class' 	=> 'Function',
			'foreign'	=> 'person',
		),
		'petitions' => array(
			'class' 	=> 'Petition',
			'foreign'	=> 'person',
		),
		'tags' => array(
			'class' 	=> 'Tag',
			'foreign'	=> 'person',
		),
	);
}

?>