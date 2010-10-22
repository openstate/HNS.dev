<?php

class Petition extends Record {
	protected $tableName = 'petitions';
	protected $softKeyDefinition = "petitioner||':'||organization";

	protected $config = array(
		'document' => array('type' => self::RELATION, 'relation' => 'Document'),
		'petitioner' => array('type' => self::RELATION, 'relation' => 'Person'),
		'organization' => array('type' => self::RELATION, 'relation' => 'Organization'),
		'status' => array('type' => self::INT),
	);
	
}

?>