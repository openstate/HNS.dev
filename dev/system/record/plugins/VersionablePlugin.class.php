<?php

require_once 'record/plugins/RecordPlugin.abstract.php';

class VersionablePlugin extends RecordPlugin {
	protected $options = array(
		'user_id' => null
	);

	protected $data = array(
		'created' => null,
		'created_by' => null,
		'updated' => null,
		'updated_by' => null,
		'revision' => null,
	);
	
	protected $db;
	protected $logDb;
	
	public function init() {
		$this->db = $this->record->getDatabase();
		$this->logDb = DBs::inst(DBs::LOGGING);
		foreach (array_keys($this->data) as $key)
			$this->record->setDataListener($key, $this);
	}
	
	public function __get($name) {
		return $this->data[$name];
	}
	
	public function __set($name, $value) {
		throw new RecordException(class_name($this->record).'.'.$name.' is read-only');
	}
	
	public function postLoad(RecordEvent $event) {
		$this->data = $this->db->query('
			SELECT created, created_by, updated, updated_by, revision
			FROM %t WHERE %l = %',
			$this->record->getTableName(), $this->record->getPkColumn(), $this->record->getPk()
		)->fetchRow();
	}
	
	public function postInsert(RecordEvent $event) {
		$data = $this->db->query('
			UPDATE %t SET created = now(), created_by = %, revision = 0 WHERE %l = %',
			$this->record->getTableName(), $this->options['user_id'],
			$this->record->getPkColumn(), $this->record->getPk()
		);
	}
	
	public function postSave(RecordEvent $event) {
		$this->db->query('BEGIN');
		try {
			$this->record->getDatabase()->query('
				UPDATE %t SET updated = now(), updated_by = %, revision = revision + 1 WHERE %l = %',
				$this->record->getTableName(), $this->options['user_id'],
				$this->record->getPkColumn(), $this->record->getPk()
			);
			$this->postLoad($event);
			$this->db->query('END');
		} catch (DatabaseQueryException $e) {
			$this->db->query('ROLLBACK');
			if (strpos($e->getError(), 'could not serialize access due to concurrent update') !== false)
				throw new RecordException('Update conflict, please resubmit update');
			else
				throw $e;
		}
		$this->logDb->query('
			INSERT INTO revisions (object_table, object_id, revision, timestamp, user_id, data) VALUES (%, %, %, %, %, %)',
			$this->record->getTableName(), $this->record->getPk(), $this->data['revision'],
			$this->data['updated'], $this->options['user_id'], json_encode($this->record->getDataArray()));
	}
}

?>