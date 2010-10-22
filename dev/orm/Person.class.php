<?php

class Person extends Record {
	protected $tableName = 'persons';

	protected $config = array(
		'id' => array(),
		'initials' => array(),
		'usualname' => array(),
		'lastname' => array(),
		'gender' => array(),
		'date_birth' => array(),
		'nationality' => array(),
		'residency' => array(),
		'picture' => array(),
		'address' => array(),
		'workphone' => array(),
		'mobilephone' => array(),
		'rights' => array(),
		'website' => array(),
		'blog' => array(),
		'email' => array(),
		'bio' => array(),
		'place_birth' => array(),
		'country_birth' => array(),
		'origin_mom' => array(),
		'origin_dad' => array(),
		'marital_status' => array(),
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