<?php

class Citation extends Record {
	protected $tableName = 'citations';
	protected $softKeyDefinition = 'citation';

	protected $config = array(
		'document' => array('type' => self::RELATION, 'relation' => 'Document'),
		'person' => array('type' => self::RELATION, 'relation' => 'Person'),
		'organization' => array('type' => self::RELATION, 'relation' => 'Organization'),
		'citation' => array('type' => self::STRING, 'length' => 250),
	);
	
}

?>