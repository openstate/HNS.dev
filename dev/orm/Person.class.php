<?php

class Person extends Record {
	protected $tableName = 'persons';

	protected $config = array(
		'id' => array(),
		'initials' => array('type' => self::STRING),
		'usualname' => array('type' => self::STRING),
		'lastname' => array('type' => self::STRING),
		'gender' => array('type' => self::INT),
		'date_birth' => array('type' => self::DATE),
		'nationality' => array('type' => self::INT),
		'residency' => array('type' => self::STRING),
		'picture' => array('type' => self::STRING),
		'address' => array('type' => self::STRING),
		'workphone' => array('type' => self::STRING),
		'mobilephone' => array('type' => self::STRING),
		'rights' => array('type' => self::STRING),
		'website' => array('type' => self::STRING),
		'blog' => array('type' => self::STRING),
		'email' => array('type' => self::STRING),
		'bio' => array('type' => self::STRING),
		'place_birth' => array('type' => self::STRING),
		'country_birth' => array('type' => self::STRING),
		'origin_mom' => array('type' => self::INT),
		'origin_dad' => array('type' => self::INT),
		'marital_status' => array('type' => self::INT),
	);
	
	protected $hasManyConfig = array(
		'documents' => array(
			'class' 	=> 'Document',
			'local'		=> 'id',
			'foreign'	=> 'author',
		),
		'cited_documents' => array(
			'class' 	=> 'Document',
			'local'		=> 'id',
			'foreign'	=> 'id',
			'table' 	=> array(
				'class'		=> 'Citation',
				'name'		=> 'citations',
				'local'		=> 'person',
				'foreign'	=> 'document',
			),
		),
	);
	
	protected $softKeyDefinition = "usualname||' '||lastname";

}

?>