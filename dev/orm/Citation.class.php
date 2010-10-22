<?php

class Citation extends Record {
	protected $tableName = 'citations';

	protected $config = array(
		'id' => array(),
		'document' => array(),
		'person' => array(),
		'organization' => array(),
		'citation' => array(),
	);
	
	protected $softKeyDefinition = 'citation';
}

?>