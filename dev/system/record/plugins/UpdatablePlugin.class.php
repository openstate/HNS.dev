<?php

require_once 'record/plugins/RecordPlugin.abstract.php';

class UpdatablePlugin extends RecordPlugin {
	protected $options = array(
		'field' => 'updated',
	);

	public function preSave(RecordEvent $event) {
		$this->record->setData($this->options['field'], 'now'); // TODO : mysql
	}
}

?>