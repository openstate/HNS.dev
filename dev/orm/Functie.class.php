<?php

class Functie extends ApiRecord {
	protected $tableName = 'functions';
	protected $softKeyDefinition = "name";

	protected $config = array(
		'name' => array('type' => self::STRING, 'length' => 25),
		'type' => array('type' => self::LOOKUP, 'lookup' => 'function_types'),
	);

	protected $hasManyConfig = array(
		'politicians' => array(
			'class' 	=> 'Person',
			'table' 	=> array(
				'class'		=> 'Person_Function',
				'name'		=> 'persons_functions',
				'local'		=> 'function',
				'foreign'	=> 'person',
			),
		)
	);	
}

?>