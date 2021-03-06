<?php

class Document_Tag extends Record {
	protected $tableName = 'documents_tags';
	protected $softKeyDefinition = 'document';

	protected $config = array(
		'document' => array('type' => self::RELATION, 'relation' => 'Document'),
		'tag' => array('type' => self::RELATION, 'relation' => 'Tag'),
		'created' => array('type' => self::DATE),
	);
	
}

?>