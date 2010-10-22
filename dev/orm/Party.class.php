<?php

class Party extends Record {
	protected $tableName = 'parties';
	protected $softKeyDefinition = 'name';

	protected $config = array(
		'name' => array('type' => self::STRING, 'length' => 50),
		'organization' => array('type' => self::RELATION, 'relation' => 'Organization'),
		'orientation' => array('type' => self::LOOKUP, 'lookup' => 'org_orientations'),
	);
	
	protected $hasManyConfig = array(
		'functions' => array(
			'class' 	=> 'Functie',
			'table' 	=> array(
				'class'		=> 'Party_Function',
				'name'		=> 'parties_functions',
				'local'		=> 'party',
				'foreign'	=> 'function',
			),
		),
	);	
}

?>