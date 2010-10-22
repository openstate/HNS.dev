<?php

abstract class RecordPlugin extends RecordBase {
	protected $record;
	protected $options = array();

	public function __construct($record, array $options) {
		$this->record = $record;
		$this->db = $record->getDatabase();
		$this->tableName = $record->getTableName();
		$this->options = array_merge($this->options, $options);
		$this->init();
	}
}

?>