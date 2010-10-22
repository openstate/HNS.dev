<?php

class Resume extends ApiRecord {
	protected $tableName = 'wiz_resume';
	protected $softKeyDefinition = 'header';

	protected $config = array(
		'person' => array('type' => self::RELATION, 'relation' => 'Person'),
		'ordinal_position' => array('type' => self::INTEGER),
		'year_from' => array('type' => self::INTEGER),
		'year_to' => array('type' => self::INTEGER),
		'header' => array('type' => self::STRING, 'length' => 255),
		'content' => array('type' => self::STRING),
		'category' => array('type' => self::STRING, 'length' => 25),
		'location' => array('type' => self::STRING, 'length' => 255),
	);
	
}

?>