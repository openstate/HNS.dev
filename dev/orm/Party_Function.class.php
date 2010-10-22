<?php

class Party_Function extends ApiRecord {
	protected $tableName = 'parties_functions';
	protected $softKeyDefinition = 'party';

	protected $config = array(
		'party' => array('type' => self::RELATION, 'relation' => 'Party'),
		'function' => array('type' => self::RELATION, 'relation' => 'Functie'),
		'start_date' => array('type' => self::DATE),
		'end_date' => array('type' => self::DATE),
	);
	
}

?>