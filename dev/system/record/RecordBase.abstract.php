<?php

require_once 'record/RecordEvent.class.php';

class RecordException extends Exception {}
abstract class RecordBase {
	protected $db;
	protected $dirty = true;
	protected $parent = null;
	protected $tableName = '';
	protected $data = array();
	protected $dataListeners = array();
	protected $parentConfig = null;
	

	protected function save() {}
	protected function buildQuery($query, $n, $conf, array $returnPath, Database $db) {}
	protected function propagateResults($rows, $returnPath) {}
	
	protected function loadFromArray($data) {}	
	protected function getMetaData($withHasOne = true, $skipAlias = false, $columnPrefix = '', $tableAlias = 't') {}

	// protected functions necessary for plugins that need access to protected record properties, $record->property not allowed!
	protected function getDatabase() {
		return $this->db;
	}

	public function getDataArray() {
		return $this->data;
	}

	public function getTableName() {
		return $this->tableName;
	}

	protected function getData($name) { 
		if (isset($this->data[$name]))
			return $this->data[$name];
	}

	protected function setData($name, $value) {
		$this->data[$name] = $value;
		$this->dirty = true;
	}

	protected function setDataListener($name, $listener) {
		$this->dataListeners[$name] = $listener;
	}

	public function init() {}

	public function preLoad(RecordEvent $event) {}
	public function postLoad(RecordEvent $event) {}

	public function preSave(RecordEvent $event) {}
	public function postSave(RecordEvent $event) {}

	public function preInsert(RecordEvent $event) {}
	public function postInsert(RecordEvent $event) {}

	public function preUpdate(RecordEvent $event) {}
	public function postUpdate(RecordEvent $event) {}

	public function preDelete(RecordEvent $event) {}
	public function postDelete(RecordEvent $event) {}
}