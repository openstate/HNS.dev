<?php

class Organization extends Record {
	protected $tableName = 'organizations';
	protected $softKeyDefinition = 'name';

	protected $config = array(
		'name' => array('type' => self::STRING, 'length' => 50),
		'type' => array('type' => self::LOOKUP, 'lookup' => 'org_types'),
		'area' => array('type' => self::LOOKUP, 'lookup' => 'sys_regions'),
		'description' => array('type' => self::STRING, 'length' => 250),
		'orientation' => array('type' => self::LOOKUP, 'lookup' => 'org_orientations'),
		'child' => array('type' => self::INT),
		'mother' => array('type' => self::RELATION, 'relation' => 'Organization'),
	);
	
	protected $hasManyConfig = array(
		'children' => array(
			'class' 	=> 'Organization',
			'foreign'	=> 'mother',
		),
		'citations' => array(
			'class' 	=> 'Citation',
			'foreign'	=> 'organization',
		),
		'documents' => array(
			'class' 	=> 'Document',
			'foreign'	=> 'submitter',
		),
		'parties' => array(
			'class' 	=> 'Party',
			'foreign'	=> 'organization',
		),
		'petitions' => array(
			'class' 	=> 'Petition',
			'foreign'	=> 'organization',
		),
		'tags' => array(
			'class' 	=> 'Tag',
			'table' 	=> array(
				'class'		=> 'Organization_Tag',
				'name'		=> 'organizations_tags',
				'local'		=> 'organization',
				'foreign'	=> 'tag',
			),
		),
	);
}

?>