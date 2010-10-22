<?php

class Person_Function extends ApiRecord {
	protected $tableName = 'persons_functions';
	protected $softKeyDefinition = 'person';

	protected $config = array(
		'person' => array('type' => self::RELATION, 'relation' => 'Person'),
		'function' => array('type' => self::RELATION, 'relation' => 'Functie'),
		'start_date' => array('type' => self::DATE),
		'end_date' => array('type' => self::DATE),
	);
	
}

?>