<?php

class Organization extends Record {
	protected $tableName = 'organizations';

	protected $config = array(
		'id' => array(),
		'name' => array(),
		'type' => array(),
		'area' => array(),
		'description' => array(),
		'orientation' => array(),
		'child' => array(),
		'mother' => array(),
	);
}

?>