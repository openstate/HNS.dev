<?php

class Vote extends ApiRecord {
	protected $tableName = 'votes';
	protected $softKeyDefinition = 'vote';

	protected $config = array(
		'person' => array('type' => self::RELATION, 'relation' => 'Person'),
		'document' => array('type' => self::RELATION, 'relation' => 'Document'),
		'vote' => array('type' => self::LOOKUP, 'lookup' => 'vote_types'),
	);
	
}

?>