<?php

class Organization extends Record {
	protected $tableName = 'parties';
	protected $softKeyDefinition = 'organization';

	protected $config = array(
		'organization' => array('type' => self::RELATION, 'relation' => 'Organization'),
		'orientation' => array('type' => self::LOOKUP, 'lookup' => 'org_orientations'),
	);
}

?>