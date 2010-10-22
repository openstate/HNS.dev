<?php

class Region extends ApiRecord {
	protected $tableName = 'sys_regions';
	protected $softKeyDefinition = 'name';
	
	protected $config = array(
		'name' => array('type' => self::STRING, 'length' => 250),
		'level' => array('type' => self::INT),
		'parent' => array('type' => self::INT),
		'hidden' => array('type' => self::INT, 'default' => 1)
	);

	// Overrides ApiRecord->init() Versionable plugin
	public function init() {}

	protected $hasManyConfig = array(
/* EXAMPLE
		'authors' => array(
			'class' 	=> 'Person',
			'table' 	=> array(
				'class'		=> 'Author',
				'name'		=> 'authors',
				'local'		=> 'document',
				'foreign'	=> 'person',
			),
		),*/
	);
}

?>