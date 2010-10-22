<?php

require_once 'record/plugins/RecordPlugin.abstract.php';
require_once 'Developer.class.php';

class CreatedUpdatedByPlugin extends RecordPlugin {

	public function preInsert(RecordEvent $event) {
		// $this->record->setData('created', 'now'); //Filled by default?!
		$this->record->setData('created_by', Developer::getInstance()->getId());
		// $this->record->setData('revision', '1'); //Filled by default?!
	}
	
	public function preSave(RecordEvent $event) {
		$this->record->setData('updated', 'now');
		$this->record->setData('updated_by', Developer::getInstance()->getId());
		$this->record->setData('revision', $this->record->getData('revision')++);
	}
}

?>