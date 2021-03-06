<?php

class Petition extends ApiRecord {
	protected $tableName = 'petitions';
	protected $softKeyDefinition = "petitioner||':'||organization";

	protected $config = array(
		'document' => array('type' => self::RELATION, 'relation' => 'Document'),
		'petitioner' => array('type' => self::RELATION, 'relation' => 'Person'),
		'organization' => array('type' => self::RELATION, 'relation' => 'Organization'),
		'status' => array('type' => self::LOOKUP, 'lookup' => 'petitions_status'),
	);
	
}

?>