<?php

class Tag extends Record {
	protected $tableName = 'tags';
	protected $softKeyDefinition = 'name';

	protected $config = array(
		'name' => array('type' => self::STRING, 'length' => 50),
		'created' => array('type' => self::DATE),
		'updated' => array('type' => self::DATE),
	);

	protected $hasManyConfig = array(
		'documents' => array(
			'class' 	=> 'Document',
			'table' 	=> array(
				'class'		=> 'Document_Tag',
				'name'		=> 'documents_tags',
				'local'		=> 'tag',
				'foreign'	=> 'document',
			),
		),
		'persons' => array(
			'class' 	=> 'Person',
			'table' 	=> array(
				'class'		=> 'Person_Tag',
				'name'		=> 'persons_tags',
				'local'		=> 'tag',
				'foreign'	=> 'person',
			),
		),
		'organizations' => array(
			'class' 	=> 'Organization',
			'table' 	=> array(
				'class'		=> 'Organization_Tag',
				'name'		=> 'organizations_tags',
				'local'		=> 'tag',
				'foreign'	=> 'organization',
			),
		),
	);
}

?>