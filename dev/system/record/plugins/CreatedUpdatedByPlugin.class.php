<?php

require_once 'record/plugins/RecordPlugin.abstract.php';
require_once 'Developer.class.php';

class CreatedUpdatedByPlugin extends RecordPlugin {

	public function __construct($record, array $options) {
		parent::__construct($record, $options);
		// Add the extra columns required to the records config
		if (!array_key_exists('created', $record->config))
			$record->config['created'] = array('writability' => self::READONLY);
		if (!array_key_exists('created_by', $record->config))
			$record->config['created_by'] = array('writability' => self::READONLY);
		if (!array_key_exists('updated', $record->config))
			$record->config['updated'] = array('writability' => self::READONLY);
		if (!array_key_exists('updated_by', $record->config))
			$record->config['updated_by'] = array('writability' => self::READONLY);		
		if (!array_key_exists('revision', $record->config))
			$record->config['revision'] = array('writability' => self::READONLY);
	}
	
	public function preInsert(RecordEvent $event) {
		// $this->record->setData('created', 'now'); //Filled by default?!
		$this->record->setData('created_by', Developer::getInstance()->getId());
		// $this->record->setData('revision', '1'); //Filled by default?!
		parent::preInsert($event);
	}

	public function preSave(RecordEvent $event) {
		$this->record->setData('updated', 'now');
		$this->record->setData('updated_by', Developer::getInstance()->getId());
		$this->record->setData('revision', $this->record->getData('revision')++);
		parent::preSave($event);
	}
}

?>