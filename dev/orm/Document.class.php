<?php

class Document extends Record {
	protected $tableName = 'documents';
	protected $softKeyDefinition = 'title';
	
	protected $config = array(
		'title' => array('type' => self::STRING, 'length' => 50),
		'source' => array('type' => self::STRING, 'length' => 250),
		'content' => array('type' => self::STRING, 'length' => 40),
		'timestamp' => array('type' => self::DATE),
		'region' => array('type' => self::LOOKUP, 'lookup' => 'sys_regions'),
		'vote_date' => array('type' => self::DATE),
		'summary' => array('type' => self::STRING, 'length' => 250),
		'type' => array('type' => self::LOOKUP, 'lookup' => 'doc_types'),
		'result' => array('type' => self::INT),
		'submitter' => array('type' => self::RELATION, 'relation' => 'Organization'),
		'category' => array('type' => self::INT),
	);
	
	protected $hasManyConfig = array(
		'authors' => array(
			'class' 	=> 'Persons',
			'table' 	=> array(
				'class'		=> 'Author',
				'name'		=> 'authors',
				'local'		=> 'document',
				'foreign'	=> 'person',
			),
		),
		'citations' => array(
			'class' 	=> 'Citation',
			'foreign'	=> 'document',
		),
		'petitions' => array(
			'class' 	=> 'Petition',
			'foreign'	=> 'document',
		),
		'tags' => array(
			'class' 	=> 'Tag',
			'table' 	=> array(
				'class'		=> 'Document_Tag',
				'name'		=> 'documents_tags',
				'local'		=> 'document',
				'foreign'	=> 'tag',
			),
		),
	);
}

?>