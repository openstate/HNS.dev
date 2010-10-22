<?php

class Person extends ApiRecord {
	protected $tableName = 'persons';
	protected $softKeyDefinition = "usualname||' '||lastname";

	protected $config = array(
		'initials' => array('type' => self::STRING, 'length' => 10),
		'usualname' => array('type' => self::STRING, 'length' => 25),
		'lastname' => array('type' => self::STRING, 'length' => 25),
		'gender' => array('type' => self::STRING, 'length' => 1, 'regex' => '!^(m|f)$!'),
		'date_birth' => array('type' => self::DATE),
		'nationality' => array('type' => self::LOOKUP, 'lookup' => 'sys_nationalities'),
		'residency' => array('type' => self::STRING, 'length' => 50),
		'picture' => array('type' => self::STRING, 'length' => 250, 'regex' => '!^https?://!'),
		'address' => array('type' => self::STRING, 'length' => 50),
		'workphone' => array('type' => self::STRING, 'length' => 15),
		'mobilephone' => array('type' => self::STRING, 'length' => 15),
		'rights' => array('type' => self::STRING, 'length' => 50),
		'website' => array('type' => self::STRING, 'length' => 50, 'regex' => '!^https?://!'),
		'blog' => array('type' => self::STRING, 'length' => 50, 'regex' => '!^https?://!'),
		'email' => array('type' => self::STRING, 'length' => 50, 'regex' => '!^.+@.+\..+$!'),
		'bio' => array('type' => self::STRING, 'length' => 50),
		'place_birth' => array('type' => self::STRING, 'length' => 50),
		'origin_mom' => array('type' => self::LOOKUP, 'lookup' => 'sys_countries'),
		'origin_dad' => array('type' => self::LOOKUP, 'lookup' => 'sys_countries'),
		'marital_status' => array('type' => self::LOOKUP, 'lookup' => 'marital_status'),
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
		'author_of' => array(
			'class' 	=> 'Document',
			'table' 	=> array(
				'class'		=> 'Author',
				'name'		=> 'authors',
				'local'		=> 'person',
				'foreign'	=> 'document',
			),
		),
		'functions' => array(
			'class' 	=> 'Functie',
			'table' 	=> array(
				'class'		=> 'Person_Function',
				'name'		=> 'persons_functions',
				'local'		=> 'person',
				'foreign'	=> 'function',
			),
		),
		'petitions' => array(
			'class' 	=> 'Petition',
			'foreign'	=> 'person',
		),
	);
}

?>