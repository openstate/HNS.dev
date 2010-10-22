<?php

class Author extends Record {
	protected $tableName = 'authors';
	protected $softKeyDefinition = 'document';

	protected $config = array(
		'document' => array('type' => self::RELATION, 'relation' => 'Document'),
		'person' => array('type' => self::RELATION, 'relation' => 'Person'),
		'order' => array('type' => self::INT),
	);
	
}

?>