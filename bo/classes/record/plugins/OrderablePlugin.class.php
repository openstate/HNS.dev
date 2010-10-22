<?php

require_once 'record/plugins/RecordPlugin.abstract.php';

class OrderablePlugin extends RecordPlugin {
	protected $max;
	protected $min;

	protected $options = array(
		'field' => 'ordering',
		'fields' => array(),
	);

	public function preInsert(RecordEvent $event) {
		$field = $this->options['field'];
		$this->record->$field = $this->getMaxOrder() + 1;
	}

	public function postDelete(RecordEvent $event) {
		$field = $this->options['field'];
		$this->db->query('UPDATE %l SET %2t = %2t - 1 WHERE %2t > %3'.$this->getClauses(), $this->tableName, $field, $this->record->$field);
	}

	private function getClauses() {
		$clauses = array();
		foreach ($this->options['fields'] as $field) {
			if (is_null($this->record->$field))
				$clauses[] = $this->db->formatQuery('%t IS NULL', $field);
			elseif (is_object($this->record->$field))
				$clauses[] = $this->db->formatQuery('%t = %', $field, $this->record->$field->id);
			else
				$clauses[] = $this->db->formatQuery('%t = %', $field, $this->record->$field);
		}

		return !empty($clauses) ? $this->db->formatQuery(' AND %l', implode(' AND ', $clauses)) : '';
	}

	private function move($value) {
		$field = $this->options['field'];

		if ($this->record->$field + $value < 1 || $this->record->$field + $value > $this->getMaxOrder()) return;

		$this->record->$field += $value;

		$this->db->query('BEGIN');
		try {
			$this->db->query('UPDATE %l SET %t = 0 WHERE id = %', $this->tableName, $field, $this->record->id);
			$this->db->query('UPDATE %1l SET %2t = %2t - %3 WHERE %2t >= %4 AND %2t <= %5'.$this->getClauses(), $this->tableName, $field, $value/abs($value), min($this->record->$field, $this->record->$field - $value), max($this->record->$field, $this->record->$field - $value));
			$this->db->query('UPDATE %l SET %t = % WHERE id = %', $this->tableName, $field, $this->record->$field, $this->record->id);
			$this->db->query('COMMIT');
		} catch (Exception $e) {
			$this->db->query('ROLLBACK');
			$this->ordering -= $value;
			throw($e);
		}
	}

	public function up($value = 1) {
		$this->move(0 - (int) $value);
	}

	public function down($value = 1) {
		$this->move((int) $value);
	}

	public function getMaxOrder() {
		if (!ctype_digit($this->max))
			$this->max = (int) $this->db->query('SELECT max(%t) FROM %l WHERE 1 = 1'.$this->getClauses(), $this->options['field'], $this->tableName)->fetchCell();
		return $this->max;
	}
	
	public function getMinOrder() {
		if (!ctype_digit($this->min))
			$this->min = (int) $this->db->query('SELECT min(%t) FROM %l WHERE 1 = 1'.$this->getClauses(), $this->options['field'], $this->tableName)->fetchCell();
		return $this->min;
	}
}

?>