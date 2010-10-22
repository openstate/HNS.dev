<?php

class Organization_Tag extends Record {
	protected $tableName = 'organizations_tags';
	protected $softKeyDefinition = 'organization';

	protected $config = array(
		'organization' => array('type' => self::RELATION, 'relation' => 'Organization'),
		'tag' => array('type' => self::RELATION, 'relation' => 'Tag'),
		'created' => array('type' => self::DATE),
	);
	
	protected $hasOneConfig = array(
		'organizations' => array(
			'class' 	=> 'Organization',
			'local'		=> 'organization',
			'foreign'	=> 'id',
		),
		'tags' => array(
			'class' 	=> 'Tag',
			'local'		=> 'tag',
			'foreign'	=> 'id',
		),
	);
}

?>