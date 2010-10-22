<?php

abstract class ApiRecord extends Record {
	/* 'Soft' key sql definition (need not return a globally unique value */
	protected $softKeyDefinition = 'id';

	/* Load a record by its soft key */
	public function loadBySoftKey($key) {
		$rows = $this->db->query('SELECT id FROM %t WHERE %l = % LIMIT 2', $this->tableName, $this->softKeyDefinition, $key)->fetchAllCells();
		if (!count($rows))
			throw new RecordException('Soft key \''.$key.'\' in record '.get_class($this).' not found');
		elseif (count($rows) > 1)
			/* If the soft key isn't unique, no record will be loaded */
			throw new RecordException('Soft key \''.$key.'\' in record '.get_class($this).' not unique');
		$this->load($rows[0]);
	}
	
	/* Generate the soft key value for this record */
	public function softKey() {
		return $this->db->query('SELECT %l FROM %t WHERE id = %', $this->softKeyDefinition, $this->tableName, $this->id)->fetchCell();
	}
	
	/* Get the soft key definition for this record */
	public function getSoftKeyDefinition() {
		return $this->softKeyDefinition;
	}
	
	/* Register the taggle plugin and set the tag owner to the api developer id */
	protected function registerTaggablePlugin() {
		try {
			$this->registerPlugin('Taggable', array('owner_id' => DataStore::get('api_user')->id));
		} catch (DataStoreException $e) {
			$this->registerPlugin('Taggable', array());
		}
		$this->hasManyConfig['tags'] = array(
			'query' => $this->db->formatQuery(
				'SELECT name, SUM(weight) AS weight, object_id, MIN(id) AS id FROM tags '.
				'WHERE object_table = % GROUP BY name, object_id',
				$this->tableName),
			'soft_key' => 'name',
			'select_all' => array('name', 'weight'),
			'order' => 'weight',
			'direction' => 'desc',
			'xml_tag' => 'tag',
			'local' => $this->pkColumn,
			'foreign' => 'object_id',
		);
	}
	
	/* Get select-all properties */
	public function getSelectAll() {
		return array_keys($this->config);
	}
	
	/* Get sql query to handle a given field (used for lookup tables and relations), given a sql query for the raw value.
	   If the result value is an array, the first value is assumed to be the query, any additional key/value pairs are
	   added to the select fields if possible */
	public function getColumnQuery($column, $value) {
		if (@$this->config[$column]['type'] == self::LOOKUP)
			return $this->db->formatQuery('(SELECT name FROM %t WHERE id = %l)', $this->config[$column]['lookup'], $value);
		elseif (@$this->hasOneConfig[$column]) {
			$obj = self::getInstance($this->hasOneConfig[$column]['class']);
			return array(
				$this->db->formatQuery('(SELECT %l FROM %t WHERE id = %l)', $obj->softKeyDefinition, $obj->tableName, $value),
				strtolower(get_class($obj)) => $value
			);
		} else
			return false;
	}

	/* Get an instance of a record object by name */
	public static function getInstance($name) {
		// Convert SOME_Record_TYPE to Some_Record_Type
		$name = implode('_', array_map('ucfirst', explode('_', strtolower($name))));
		ob_start();
		include_once($name.'.class.php');
		$ob = ob_get_clean();
		if (DEVELOPER && !HIDE_SOME_WARNINGS) echo $ob;
		if (!class_exists($name, false))
			throw new RecordException('Unknown record class '.$name);
		$obj = new $name();
		if (!($obj instanceof ApiRecord))
			throw new RecordException('Unknown record class '.$name);
		return $obj;
	}
	
	public function init() {
		try {
			$this->registerPlugin('Versionable', array('user_id' => DataStore::get('api_user')->id));
		} catch (DataStoreException $e) {
			$this->registerPlugin('Versionable');
		}
	}
	
}

?>