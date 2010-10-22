<?php

class Document extends Record {
	protected $tableName = 'documents';

	protected $config = array(
		'id' => array(),
		'title' => array(),
		'author' => array(),
		'source' => array(),
		'content' => array(),
		'timestamp' => array(),
		'region' => array(),
		'vote_date' => array(),
		'summary' => array(),
		'type' => array(),
		'result' => array(),
		'submitter' => array(),
		'category' => array(),
	);
	
	protected $hasOneConfig = array(
		'author' => array(
			'class' => 'Person',
			'local' => 'author',
			'foreign' => 'id',
		),
	);
	
	protected $softKeyDefinition = 'title||\' \'||author';
}

?>