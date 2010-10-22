<?php

require_once 'record/plugins/RecordPlugin.abstract.php';

class SluggablePlugin extends RecordPlugin {
	protected $options = array(
		'field' => 'slug',
		'fields' => array('title'),
		'unique' => array(),
		'reslugOnUpdate' => false,
		'excludes' => array(),
	);

	public function preInsert(RecordEvent $event) {
		$field = $this->options['field'];

		if (!$this->record->$field) {
			$slug = $slugHelper = $this->buildSlug($this->record);

			$index = 1;
			$select = $this->addUniqueClauses($this->record->select());
			$selectHelper = clone $select;
			while (in_array($slugHelper, $this->options['excludes']) || $selectHelper->where('t1.%t = %', $this->options['field'], $slugHelper)->getCount() > 0) {
				$selectHelper = clone $select;
				$slugHelper = $slug.'-'.$index;
				$index++;
			}

			$this->record->$field = $slugHelper;
		}
	}

	public function preUpdate(RecordEvent $event) {
		if ($this->options['reslugOnUpdate']) {
			$field = $this->options['field'];

			$slug = $slugHelper = $this->buildSlug($this->record);

			$index = 1;
			$select = $this->addUniqueClauses($this->record->select());
			$select->where('t1.%t != %', $this->record->getPkColumn(), $this->record->getPk());
			$selectHelper = clone $select;
			while ($selectHelper->where('t1.%t = %', $this->options['field'], $slugHelper)->getCount() > 0) {
				$selectHelper = clone $select;
				$slugHelper = $slug.'-'.$index;
				$index++;
			}

			$this->record->$field = $slugHelper;

		}
	}

	protected function addUniqueClauses(RecordQuery $q) {
		foreach ($this->options['unique'] as $field) {
			if (is_null($this->record->$field))
				$q->where('%t IS NULL', $field);
			else
				$q->where('%t = %', $field, $this->record->$field);
		}
		return $q;
	}

	protected function buildSlug($record)	{
		$value = '';
		foreach ($this->options['fields'] as $field)
			$value = $record->$field.' ';

		require_once 'String.class.php';
		return String::urlize($value);
	}

	public function loadBySlug($slug) {
		$this->record->loadByUnique($this->options['field'], $slug);
	}
}

?>