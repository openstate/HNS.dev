<?php

class Author extends ApiRecord {
	protected $tableName = 'authors';
	protected $softKeyDefinition = 'document';

	protected $config = array(
		'person' => array('type' => self::RELATION, 'relation' => 'Person'),
		'document' => array('type' => self::RELATION, 'relation' => 'Document'),
		'vote' => array('type' => self::LOOKUP, 'lookup' => 'vote_types'),
	);
	
}

?>