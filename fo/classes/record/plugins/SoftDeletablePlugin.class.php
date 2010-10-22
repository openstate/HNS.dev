<?php

require_once 'record/plugins/RecordPlugin.abstract.php';

class SoftDeletablePlugin extends RecordPlugin {
	protected $options = array(
		'field' => 'deleted',
	);

	public function preDelete(RecordEvent $event) {
		$event->skip();
		$this->record->{$this->options['field']} = 'now'; // TODO : mysql
		$this->record->save();
	}
}

?>