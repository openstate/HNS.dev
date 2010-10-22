<?php

class Organization extends ApiRecord {
	protected $tableName = 'organizations';
	protected $softKeyDefinition = 'name';

	protected $config = array(
		'name' => array('type' => self::STRING, 'length' => 50),
		'type' => array('type' => self::LOOKUP, 'lookup' => 'org_types'),
		'area' => array('type' => self::LOOKUP, 'lookup' => 'sys_regions'),
		'description' => array('type' => self::STRING, 'length' => 250),
		'orientation' => array('type' => self::LOOKUP, 'lookup' => 'org_orientations'),
		'child' => array('type' => self::RELATION, 'relation' => 'Organization'),
		'mother' => array('type' => self::RELATION, 'relation' => 'Organization'),
	);
	
	protected $hasManyConfig = array(
		'children' => array(
			'class' 	=> 'Organization',
			'foreign'	=> 'mother',
		),
		'mother' => array(
			'class' 	=> 'Organization',
			'foreign'	=> 'child',
		),
		'citations' => array(
			'class' 	=> 'Citation',
			'foreign'	=> 'organization',
		),
		'documents' => array(
			'class' 	=> 'Document',
			'foreign'	=> 'submitter_organization',
		),
		'parties' => array(
			'class' 	=> 'Party',
			'foreign'	=> 'organization',
		),
		'petitions' => array(
			'class' 	=> 'Petition',
			'foreign'	=> 'organization',
		),
	);
}

?>