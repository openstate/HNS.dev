<?php

class Function extends Record {
	protected $tableName = 'functions';
	protected $softKeyDefinition = "person||':'||type";

	protected $config = array(
		'person' => array('type' => self::RELATION, 'relation' => 'Person'),
		'type' => array('type' => self::INT),
	);
	
}

?>