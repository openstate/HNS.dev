<?php

class Person_Tag extends Record {
	protected $tableName = 'persons_tags';
	protected $softKeyDefinition = 'person';

	protected $config = array(
		'person' => array('type' => self::RELATION, 'relation' => 'Person'),
		'tag' => array('type' => self::RELATION, 'relation' => 'Tag'),
		'created' => array('type' => self::DATE),
	);
	
}

?>