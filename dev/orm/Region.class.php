<?php

class Region extends ApiRecord {
	protected $tableName = 'sys_regions';
	protected $softKeyDefinition = 'name';

	protected $config = array(
		'name' => array('type' => self::STRING, 'length' => 250),
		'level' => array('type' => self::INT),
		'parent' => array('type' => self::INT),
		'hidden' => array('type' => self::INT, 'default' => 1)
	);

	// Overrides ApiRecord->init() Versionable plugin
	public function init() {}

	protected $hasManyConfig = array(
/* EXAMPLE
		'authors' => array(
			'class' 	=> 'Person',
			'table' 	=> array(
				'class'		=> 'Author',
				'name'		=> 'authors',
				'local'		=> 'document',
				'foreign'	=> 'person',
			),
		),*/
	);

	/**
	 * Override. Regions cannot be saved.
	 * (non-PHPdoc)
	 * @see system/record/Record#save()
	 */
	public function save() {
		throw new RightsException('Regions cannot be saved.');

		return parent::save();
	}

	/**
	 * Override. Regions cannot be deleted.
	 * (non-PHPdoc)
	 * @see system/record/Record#delete()
	 */
	public function delete(){
		throw new RightsException('Regions cannot be deleted.');
	}
}

?>