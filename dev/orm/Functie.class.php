<?php

class Functie extends ApiRecord {
	protected $tableName = 'functions';
	protected $softKeyDefinition = "person||':'||type";

	protected $config = array(
		'name' => array('type' => self::STRING, 'length' => 25),
		'created' => array('type' => self::DATE),
		'type' => array('type' => self::LOOKUP, 'lookup' => 'function_types'),
	);
		
}

?>