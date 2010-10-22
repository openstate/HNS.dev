<?php

class Tag extends Record {
	protected $tableName = 'tags';
	protected $softKeyDefinition = 'value';

	protected $config = array(
		'person' => array('type' => self::RELATION, 'relation' => 'Person'),
		'document' => array('type' => self::RELATION, 'relation' => 'Document'),
		'organization' => array('type' => self::RELATION, 'relation' => 'Organization'),
		'tag' => array('type' => self::INT),
		'value' => array('type' => self::STRING, 'length' => 50),
		'created' => array('type' => self::DATE),
		'updated' => array('type' => self::DATE),
	);
	
}

?>