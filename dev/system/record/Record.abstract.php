<?php

require_once 'database/DBs.class.php';
require_once 'record/RecordBase.abstract.php';
require_once 'record/RecordQuery.class.php';
require_once 'record/RecordCollection.class.php';
require_once 'record/RecordCache.class.php';

class RecordNotFoundException extends RecordException {}
abstract class Record extends RecordBase {

	protected $pkColumn = 'id';  // Name of the id column, refers to $config entry.

	// snamed constants as strings for better understanding when var_dumping them
	const NORMAL = 'normal';
	const READONLY = 'readOnly';
	const NONWRITTEN = 'nonWritten';

	const LAZY = 'lazy';
	const GREEDY = 'greedy';
	const PASSTHROUGH = 'passthrough';

	const RELATION_SEPARATOR = '__';

	const LOAD_NONE = 'load_none';
	const LOAD_ALL = 'load_all';
	const LOAD_DEFAULT = 'load_default';
	
	const FLOAT = 'float';
	const INT = 'int';
	const STRING = 'string';
	const LOOKUP = 'lookup';

	/*
		Holds column settings for this table
		format:
		key = column
		value = config array.

		the config array has the following keys (all optional)
		- writability				One of self::NORMAL, self::READONLY, self::NONWRITTEN
			- self::NORMAL		a normal column, this is the default
			- self::READONLY		can not be __set, but still writen to database
			- self::NONWRITTEN	can not be __set and won't be written to the database
		- loading					One of self::LAZY, self::GREEDY, self::PASSTHROUGH
			- self::GREEDY		Loaded on loading of the record (default)
			- self::LAZY			Loaded when accessed
			- self::PASSTHROUGH	Loaded when accessed but not internally stored (Useful for blobs)
		- type						One of self::FLOAT, self::INT, self::STRING, self::LOOKUP
		- pattern					Regex pattern (for self::STRING) or lookup table name (for self::LOOKUP)
	*/
	protected $config = array();

	/*
		Holds the (default)data,
		if a column isn't defined in here null will be returned when tried to be accessed
	*/
	protected $data = array();

	/*
		Holds possible listeners on the data array,
		will be checked when data properties are accessed
	*/
	protected $dataListeners = array();

	/*
		Holds the has_one relations
		key = relation/accessor name
		value = config array.

		The config array has the following mandatory keys:
		- class					The name of the record class that is on the other side of this relation
		- local					The field in this record that is used for joining
		- foreign					The field in the other record (class) that is used for joining

		The config array has the following optional keys:
		- loading					One of self::LAZY, self::GREEDY, self::PASSTHROUGH
			- self::GREEDY		Loaded on loading of the record
			- self::LAZY			Loaded when accessed (default)
			- self::PASSTHROUGH	Loaded when accessed but not internally stored (Useful for blobs)

	*/
	protected $hasOneConfig = array();
	/*
		Holds the hasOne relation data
	*/
	protected $hasOneData = array();

	/*
		Holds the has_many relations
		key = relation/accessor name
		value = config array.

		The config array has the following mandatory keys:
		- class					The name of the record class that is on the other side of this relation
		- local					The field in this record that is used for joining
		- foreign					The field in the other record (class) that is used for joining

		The config array has the following optional keys:
		- where					Where clause to add to the relation query, note that it's passed through formatQuery, so use double % if you want %
								"t1" is the alias of the relation table, "j" will be the alias of optional junction table.
		- loading					One of self::LAZY, self::PASSTHROUGH
			- self::LAZY			Loaded when accessed (default)
			- self::PASSTHROUGH	Loaded when accessed but not internally stored (Useful for blobs or large data)
		- table					Holds an array with junction table configuration.
			table config:
				- name			Table name to use as junction table
				- local			The column in the junction table that points to this record
				- foreign			The column in the junction table that points to the other record

	*/
	protected $hasManyConfig = array();
	/*
		Holds the hasMany relation data
	*/
	protected $hasManyData = array();

	/*
		Holds the registered plugins
	*/
	protected $plugins = array();

	public function __construct() {
		$this->db = DBs::inst(DBs::SYSTEM);
		$this->data[$this->pkColumn] = false;
		$this->init();
	}

	public function __destruct() {
		// http://bugs.php.net/bug.php?id=33595 still exists, till 5.3 so destroy all objects
		foreach($this->hasOneConfig as $key => $config) {
			if (isset($this->hasOneData[$key])) unset($this->hasOneData[$key]);
		}
		foreach($this->hasManyConfig as $key => $config) {
			if (isset($this->hasManyData[$key])) unset($this->hasManyData[$key]);
		}
	}

	public function __sleep() {
		return array('dirty', 'pkColumn', 'config', 'data', 'hasOneConfig', 'hasOneData', 'hasManyConfig', 'hasManyData');
	}

	public function __wakeup() {
		$this->db = DBs::inst(DBs::SYSTEM);
	}

	public function exists($id) {
		return (bool)$this->db->query('SELECT 1 FROM %t WHERE %t = %', $this->tableName, $this->pkColumn, $id)->fetchCell();
	}

	public function existsByUnique($column, $value) {
		return (bool)$this->db->query('SELECT 1 FROM %t WHERE %t = %', $this->tableName, $column, $value)->fetchCell();
	}

	protected function getMetaData($withHasOne = self::LOAD_DEFAULT, $skipAlias = false, $columnPrefix = '', $tableAlias = 't1') {
		$columns = array();
		$tables = array();

		$tables[] = $this->db->formatQuery('%t %t', $this->tableName, $tableAlias);
		foreach ($this->config as $key => $value) {
			if (isset($value['loading']) && $value['loading'] == self::PASSTHROUGH) continue; //skip passthrough columns
			$column = $this->db->formatQuery('%t.%t', $tableAlias, $key);
			if (!$skipAlias) {
				$column .= $this->db->formatQuery(' AS %t', $columnPrefix.$key);
			}
			$columns[] = $column;
		}

		if ($withHasOne !== self::LOAD_NONE) {
			$aliasIndex = 0;
			foreach ($this->hasOneConfig as $key => $value) {
				if ( (isset($value['loading']) && $value['loading'] == self::PASSTHROUGH) ) continue; //skip passthrough
				if ( (isset($value['loading']) && $value['loading'] == self::GREEDY) || $withHasOne === self::LOAD_ALL) {
					$aliasIndex++;
					$obj = new $value['class']();
					$relationMeta = $obj->getMetaData(self::LOAD_NONE, false, $key.self::RELATION_SEPARATOR, $tableAlias.$aliasIndex);
					$columns = array_merge($columns,$relationMeta['columns']);
					foreach ($relationMeta['tables'] as $table) {
						$tables[] = $this->db->formatQuery('LEFT JOIN %l ON %t.%t = %t.%t', $table, $tableAlias, $value['local'], $tableAlias.$aliasIndex, $value['foreign']);
					}
				}
			}
		}
		return array('columns' => $columns, 'tables' => $tables);
	}

	public function load($id, $withHasOne = self::LOAD_DEFAULT) {
		if ($this->getPk() !== false) {
			throw new RecordException('Trying to load a record by primary key "'.$id.'" that is already loaded with primary key: "'.$this->getPk().'"');
		}
		if (($data = RecordCache::get(get_class($this), $id)) === false) {
			$q = $this->select()->withHasOne($withHasOne)->where('%t.%t = %', 't1', $this->pkColumn, $id);
			try {
				if (!($data = $q->getStatement()->fetchRow()))
					throw new RecordNotFoundException('The record with the primary key "'.$id.'" could not be found');
			} catch (DatabaseQueryException $e) {
				throw new RecordNotFoundException('The record with the primary key "'.$id.'" could not be found');
			}
		}
		$this->loadFromArray($data, $withHasOne);
		return true;
	}

	public function loadByUnique($column, $value, $withHasOne = self::LOAD_DEFAULT) {
		if ($this->getPk() !== false) {
			throw new RecordException('Trying to load a record by field: "'.$column.'" with value "'.$value.'" that is already loaded with primary key: "'.$this->getPk().'"');
		}
		if (($data = RecordCache::find(get_class($this), $column, $value)) === false) {
			try {
				if ($value === null) {
					$q = $this->select()->withHasOne($withHasOne)->where('%t.%t IS NULL', 't1', $column);
				} else {
					$q = $this->select()->withHasOne($withHasOne)->where('%t.%t = %', 't1', $column, $value);
				}
				if (!($data = $q->getStatement()->fetchRow()))
					throw new RecordNotFoundException('The record with the unique key ('.$column.') "'.$value.'" could not be found');
			} catch (DatabaseQueryException $e) {
				throw new RecordNotFoundException('The record with the unique key ('.$column.') "'.$value.'" could not be found');
			}
		}
		$this->loadFromArray($data, $withHasOne);
		return true;
	}

	protected function loadFromArray($data, $withHasOne = self::LOAD_DEFAULT) {
		if ($this->getPk() !== false) {
			throw new RecordException('Trying to load a record with data that is already loaded with primary key: "'.$this->getPk().'"');
		}
		$this->data = array_merge($this->data, array_intersect_key($data, $this->config));
		if ($withHasOne !== self::LOAD_NONE) {
			foreach ($this->hasOneConfig as $key => $value) {
				if (isset($data[$key.'__'.$value['foreign']])) {
					$relationData = $this->filterRelation($key, $data);
					$this->hasOneData[$key] = new $value['class']();
					$this->hasOneData[$key]->loadFromArray($relationData, self::LOAD_NONE);
					$this->hasOneData[$key]->parent = $this;
					$conf = $this->hasOneConfig[$key];
					$conf['name'] = $key;
					$this->hasOneData[$key]->parentConfig = $conf;
				}
			}
		}
		RecordCache::add(get_class($this), $this->pkColumn, $this->data);
		$this->dirty = false;
	}

	public function delete() {
		if (!$this->data[$this->pkColumn])
			return;

		if ($this->parent && $this->parent instanceof RecordCollection)
			$this->parent->remove($this);

		//TODO: cascade for mysql! transaction handling
		$event = new RecordEvent();
		$this->notifyListeners('preDelete', $event);
		if ($event->skip) // TODO : maybe we shouldn't skip postDelete notifications
			return;

		RecordCache::remove(get_class($this), $this->data[$this->pkColumn]);
		$this->db->query('DELETE FROM %t WHERE %t = %', $this->tableName, $this->pkColumn, $this->data[$this->pkColumn]);

		$this->notifyListeners('postDelete', $event);

		$this->data[$this->pkColumn] = false;
		$this->dirty = false;
	}


	public function save() {
		//TODO: start a transaction?
		if ($this->dirty || !$this->getPk()) {
			RecordCache::remove(get_class($this), $this->data[$this->pkColumn]);
			$event = new RecordEvent();
			$this->notifyListeners('preSave', $event);
			if ($event->skip)
				return;

			//call save on all the has_one relations that have their pk stored locally
			foreach ($this->hasOneConfig as $key => $config) {
				if (!isset($this->hasOneData[$key])) continue;
				if ($config['local'] == $this->pkColumn) continue; //skip all the relations that have our pk stored, for those we need to save ourselfs first
				$this->hasOneData[$key]->save();
				$this->data[$config['local']] = $this->hasOneData[$key]->data[$config['foreign']];
			}

			//save this record
			if ($this->data[$this->pkColumn])
				$this->update();
			else
				$this->insert();

			//call set all the has_ones that need our id and save them
			foreach ($this->hasOneConfig as $key => $config) {
				if (!isset($this->hasOneData[$key])) continue;
				if ($config['local'] != $this->pkColumn) continue; //skip all the relations that have our pk stored
				$this->hasOneData[$key]->data[$config['foreign']] = $this->data[$config['local']];
				$this->hasOneData[$key]->save();
			}

			//call save on all the has_many relations
			foreach ($this->hasManyConfig as $key => $value) {
				if (!isset($this->hasManyData[$key])) continue;
				$this->hasManyData[$key]->save();

				if (isset($this->hasManyConfig[$key]['table'])) {
					$conf = $this->hasManyConfig[$key];
					//delete old values
					$this->db->query('DELETE FROM %t WHERE %t = %', $conf['table']['name'], $conf['table']['local'], $this->data[$this->pkColumn]);

					//find new values
					$junctions = array();
					foreach ($this->hasManyData[$key] as $el) {
						$junctions[] = (integer)$el->getPk();
					}

					//add them uniquely
					foreach (array_unique($junctions) as $junction) {
						try {
							$this->db->query('INSERT INTO %t (%t, %t) VALUES (%, %)', $conf['table']['name'], $conf['table']['local'], $conf['table']['foreign'], $this->data[$conf['local']], $junction);
						} catch (DatabaseQueryException $e) { throw $e; } //drop any foreign key violations silently
					}
					$this->hasManyData[$key]->dirty = false;
				}
			}

			RecordCache::add(get_class($this), $this->pkColumn, $this->data);

			$this->notifyListeners('postSave', $event);
			$this->dirty = false;
		}
	}

	protected function insert() {
		$event = new RecordEvent();
		$this->notifyListeners('preInsert', $event);
		if ($event->skip)
			return;

		$query = 'INSERT INTO ' . $this->tableName . ' (';
		$columns = array();
		$values = array();
		foreach ($this->config as $key => $config) {
			if (isset($config['writability']) && $config['writability'] === self::NONWRITTEN) continue;
			if ($key === $this->pkColumn) continue;
			$column = $key;
			$columns[] =  $this->db->formatQuery('%t', $column);

			$value = null;
			if (isset($this->data[$key])) $value = $this->data[$key];
			$values[] = $this->db->formatQuery('%', $value);
		}
		$query .= implode(', ', $columns) . ') VALUES(' . implode(', ', $values) . ')';
		$this->db->query($query);
		$this->data[$this->pkColumn] = $this->db->getSerialVal($this->tableName, $this->pkColumn);

		$this->notifyListeners('postInsert', $event);
	}

	protected function update() {
		$event = new RecordEvent();
		$this->notifyListeners('preUpdate', $event);
		if ($event->skip)
			return;

		$query = 'UPDATE ' . $this->tableName . ' SET ';
		$items = array();
		foreach ($this->config as $key => $config) {
			if (isset($config['writability']) && $config['writability'] === self::NONWRITTEN) continue;
			$column = $key;
			$value = null;
			if (isset($this->data[$key])) $value = $this->data[$key];

			$items[] = $this->db->formatQuery('%t = %', $column, $value);
		}

		$query .= $this->db->formatQuery('%l WHERE %t = %', implode(', ', $items), $this->pkColumn, $this->data[$this->pkColumn]);
		$this->db->query($query);

		$this->notifyListeners('postUpdate', $event);
	}

	public function buildFetchQuery($queryData) {
		$metaData = $this->getMetaData($queryData['withHasOne'], false, '', $queryData['alias']);

		$columns = $queryData['extraColumns'];
		if ($queryData['recordColumns']) {
			$columns = array_merge($metaData['columns'], $queryData['extraColumns']);
		}

		$query = 'SELECT ';
		$query .= implode(', ', $columns);
		$query .= ' FROM ';
		$query .= implode(' ', $metaData['tables']);
		if (!empty($queryData['join'])) {
			$query .= ' ' . implode(' ', $queryData['join']);
		}
		if (!empty($queryData['where'])) {
			$query .= ' WHERE (' . implode(') AND (', $queryData['where']) . ')';
		}
		if (!empty($queryData['group'])) {
			$query .= ' GROUP BY ' . implode(', ', $queryData['group']);
		}
		if (!empty($queryData['having'])) {
			$query .= ' HAVING (' . implode(') AND (', $queryData['having']) . ')';
		}
		if (!empty($queryData['order'])) {
			$query .= ' ORDER BY ' . implode(', ', $queryData['order']);
		}
		if ($queryData['limitCount']) {
			$query .= ' LIMIT ' . $queryData['limitCount'];
			if ($queryData['limitOffset'])
				$query .= ' OFFSET ' . $queryData['limitOffset'];
		}
		return $query;
	}

	protected function filterRelation($name, $data) {
		$result = array();
		foreach ($data as $key => $value) {
			if (strpos($key, $name . self::RELATION_SEPARATOR) === 0) //starts with
				$result[substr($key, strlen($name . self::RELATION_SEPARATOR))] = $value;
		}
		return $result;
	}

	protected function buildQuery($query, $n, $conf, array $returnPath, Database $db) {
		if (isset($conf['table'])) {
			$query->join('JOIN %1t %2t ON %2t.%3t = %4t.%5t', $conf['table']['name'], 'j', $conf['table']['foreign'], 't'.($n-1), $conf['foreign']);
			$query->join('JOIN %1t %2t ON %2t.%3t = %4t.%5t', $this->tableName, 't'.$n, $conf['local'], 'j', $conf['table']['local']);
		} else {
			$query->join('JOIN %1t %2t ON %2t.%3t = %4t.%5t', $this->tableName, 't'.$n, $conf['local'], 't'.($n-1), $conf['foreign']);
		}
		$returnPath[] = $conf['name'];
		if ($this->parent) {
			return $this->parent->buildQuery($query, $n+1, $this->parentConfig, $returnPath, $db);
		} else {
			if (isset($this->data[$this->pkColumn])) {
				$rows = $query->where('%t.%t = %', 't'.$n, $this->pkColumn, $this->data[$this->pkColumn])->getStatement()->fetchAllRows(false, '_groupid');
				$this->propagateResults($rows, $returnPath);
			}
			//TODO: do nothing if not persistated?
		}
	}

	/*
		propagate's the result
	*/
	protected function propagateResults($rows, $returnPath) {
		$propName = array_pop($returnPath);
		// If we're not at the last item in the returnpath
		if (count($returnPath) > 0) {
			// propagate the hasOne or the hasMany
			if (isset($this->hasOneConfig[$propName]) && isset($this->hasOneData[$propName])) {
				$this->hasOneData[$propName]->propagateResults($rows, $returnPath);
			} elseif (isset($this->hasManyConfig[$propName]) && isset($this->hasManyData[$propName])) {
				$this->hasManyData[$propName]->propagateResults($rows, $returnPath);
			}
		} else {
			// Adds the data to the hasOne
			if (isset($this->hasOneConfig[$propName])) {
				$conf = $this->hasOneConfig[$propName];
				$conf['name'] = $propName;
				if (isset($rows[$this->data[$this->pkColumn]])) {
					$obj = new $conf['class'];
					$obj->loadFromArray(reset($rows[$this->data[$this->pkColumn]]));
					$obj->parent = $this;
					$obj->parentConfig = $conf;
					$this->hasOneData[$propName] = $obj;
				}
			// Adds the data to the hasMany
			} elseif (isset($this->hasManyConfig[$propName])) {
				$conf = $this->hasManyConfig[$propName];
				$conf['name'] = $propName;
				$this->hasManyData[$propName] = new RecordCollection($conf['class']);
				$this->hasManyData[$propName]->parent = $this;
				$this->hasManyData[$propName]->parentConfig = $conf;
				if (isset($rows[$this->data[$this->pkColumn]])) {
					foreach ($rows[$this->data[$this->pkColumn]] as $row) {
						$obj = new $conf['class']();
						$obj->loadFromArray($row);
						$this->hasManyData[$propName]->add($obj);
						$this->hasManyData[$propName]->dirty = false;
					}
				}
			}
		}
	}

	protected function queryHasOne($name) {
		$this->hasOneData[$name] = null;
		$conf = $this->hasOneConfig[$name];
		$className = $conf['class'];
		if (!class_exists($className)) {
			require_once $className.'.class.php';
		}
		$obj = new $className();
		$query = $obj->select()->setAlias('t1')->addExtraColumn('%t.%t AS _groupid', 't2', $this->pkColumn)->withHasOne(self::LOAD_DEFAULT);
		$conf['name'] = $name;
		$this->buildQuery($query, 2, $conf, array(), $this->db);

	}

	protected function queryHasMany($name) {
		$this->hasManyData[$name] = null;
		$conf = $this->hasManyConfig[$name];
		$className = $conf['class'];
		if (!class_exists($className)) {
			require_once $className.'.class.php';
		}
		$obj = new $className();
		$query = $obj->select()->setAlias('t1')->addExtraColumn('%t.%t AS _groupid', 't2', $this->pkColumn)->withHasOne(self::LOAD_DEFAULT);
		if (isset($conf['where']))
			$query->where($conf['where']);
		$conf['name'] = $name;
		$this->buildQuery($query, 2, $conf, array(), $this->db);
	}

	public function refresh() {
		if (!isset($this->data[$this->pkColumn])) return;
		$this->load($this->data[$this->pkColumn]);
	}

	//get the pk value
	public function getPk() {
		return $this->data[$this->pkColumn];
	}

	public function getPkColumn() {
		return $this->pkColumn;
	}


	public function select() {
		return new RecordQuery($this->db, get_class($this));
	}
	public function selectAll() {
		return $this->select()->get();
	}

	// The following methods return an associative array of key => column value
	public function selectColumn($col, $index = false, $indexTable = false) {
		if (!isset($this->config[$col]) || ($index && !isset($this->config[$col])))
			return false;
		return $this->select()
				->addRecordColumns(false)
				->setFetchMode(RecordQuery::FETCH_ARRAY)
				->addExtraColumn('%t.%t', $indexTable ? $indexTable : 't1', $index ? $index : $this->pkColumn)
				->addExtraColumn('%t.%t', 't1', $col);
	}

	public function selectColumnAll($col) {
		if (($selectCol = $this->selectColumn($col)) !== false )
			return $selectCol->order('%t ASC', $col)->get();
		return false;
	}

	// Internal interface to ease increase/decrease-like functions
	protected function applyExpression($param) {
		$this->db->query('UPDATE %t SET %l WHERE %t = %', $this->tableName, $param, $this->pkColumn, $this->data[$this->pkColumn]);
		//FIXME: Reload the record !
	}

	public function __get($name) {
		//---- Listeners
		if (isset($this->dataListeners[$name])) {
			return $this->dataListeners[$name]->__get($name);
		//----- HasOne relations
		} elseif (isset($this->hasOneConfig[$name])) {
			if (!array_key_exists($name, $this->hasOneData)) {
				if (($data = RecordCache::find($this->hasOneConfig[$name]['class'], $this->hasOneConfig[$name]['foreign'], $this->data[$this->hasOneConfig[$name]['local']]) ) !== false) {
					$className = $this->hasOneConfig[$name]['class'];
					$this->hasOneData[$name] = new $className();
					$this->hasOneData[$name]->loadFromArray($data);
					$this->hasOneData[$name]->parent = $this;
					$conf = $this->hasOneConfig[$name];
					$conf['name'] = $name;
					$this->hasOneData[$name]->parentConfig = $conf;

				} else {
					$this->queryHasOne($name);
				}
			}
			return $this->hasOneData[$name];
		//---- Columns
		} elseif (isset($this->config[$name])) {
			if (!array_key_exists($name, $this->data)) {
				$column = $name;
				$cell = $this->db->query('SELECT %t FROM %t WHERE %t = %', $column, $this->tableName, $this->pkColumn, $this->data[$this->pkColumn])->fetchCell();
				if (isset($this->config[$name]['loading']) && $this->config[$name]['loading'] == self::PASSTHROUGH)
					return $cell;
				$this->data[$name] = $cell;
			}
			return $this->data[$name];
		//---- HasMany Relations
		} elseif (isset($this->hasManyConfig[$name])) {
			if (!array_key_exists($name, $this->hasManyData)) {
				$this->queryHasMany($name);
			}
			return $this->hasManyData[$name];
		} else {
			throw new RecordException('Attempt to read unknown property "'.get_class($this).'::$'.$name.'"');
		}
	}

	public function __set($name, $value) {
		//---- Listeners
		if (isset($this->dataListeners[$name])) {
			return $this->dataListeners[$name]->__set($name, $value);
		}
		//---- Columns
		if ($name == $this->pkColumn) throw new RecordException('Setting of the primary key is not allowed !');
		if (isset($this->hasOneConfig[$name]) && is_object($value)) {
			if ($value instanceof $this->hasOneConfig[$name]['class']) {
				$this->hasOneData[$name] = $value;
				if ($this->hasOneConfig[$name]['local'] != $this->pkColumn)
					$this->data[$this->hasOneConfig[$name]['local']] = $value->{$this->hasOneConfig[$name]['foreign']};
				$this->dirty = true;
			} else {
				throw new RecordException('Attempt to write has one relation "'.$name.'" with invalid class expected instance of "'.$this->hasOneConfig[$name]['class'].'"');
			}
		} elseif (isset($this->config[$name]) && (!isset($this->config[$name]['writability']) || $this->config[$name]['writability'] == self::NORMAL)) {
			if ($check = @$this->config[$name]['check']) {
				if ($check == self::INT && !(is_int($value) || ctype_digit($value)))
					throw new RecordException('INT expected but not found for '.get_class($this).'.'.$name);
				elseif ($check == self::FLOAT && !(is_float($value) || is_int($value) || preg_match('/^[0-9]+(\.[0-9]+)?$/', $value)))
					throw new RecordException('FLOAT expected but not found for '.get_class($this).'.'.$name);
				elseif ($check == self::STRING) {
					$pattern = @$this->config[$name]['pattern'];
					if ($pattern && !preg_match($pattern, $value))
						throw new RecordException('STRING expected but not found for '.get_class($this).'.'.$name);
				} elseif ($check == self::LOOKUP) {
					$pattern = @$this->config[$name]['pattern'];
					if ($pattern) {
						$value = $this->db->query('SELECT id FROM %t WHERE id = % OR name = %2', $this->tableName, $value)->fetchCell();
						if (!$value)
							throw new RecordException('LOOKUP expected but not found for '.get_class($this).'.'.$name);
					}
				}
			}
			if (isset($this->hasOneConfig[$name]) && !(is_int($value) || ctype_digit($value))) {
				$obj = Record::getInstance($this->hasOneConfig[$name]['class']);
				$obj->loadBySoftKey($value);
				$value = $obj->id;
			}
			$this->data[$name] = $value;
			if (!isset($this->config[$name]['relations'])) {
				foreach ($this->config as &$item)
					$item['relations'] = array();
				unset($item);
				foreach ($this->hasOneConfig as $key => $relation)
					if ($relation['local'] != $this->pkColumn)
						$this->config[$relation['local']]['relations'][] = $key;
			}
			foreach ($this->config[$name]['relations'] as $item)
				unset($this->hasOneData[$item]);
			$this->dirty = true;
		} elseif (isset($this->hasManyConfig[$name])) {
			throw new RecordException('Attempt to write to has many relation "'.$name.'"');
		} else {
			throw new RecordException('Attempt to write unknown property "'.get_class($this).'::$'.$name.'"');
		}
	}

	//usage = ->relation() , the data fetched this way isn't propagated nor stored internally.
	public function __call($name, $args){
		if (isset($this->hasManyConfig[$name])) {
			$query = new RecordQuery($this->db, $this->hasManyConfig[$name]['class']);
			$alias = 't2';
			if (isset($this->hasManyConfig[$name]['table'])) {
				$joinAlias = 'j';
				$query->join('JOIN %1t %2t ON %2t.%3t = %4t.%5t', $this->hasManyConfig[$name]['table']['name'], $joinAlias, $this->hasManyConfig[$name]['table']['foreign'], 't1', $this->hasManyConfig[$name]['foreign']);
				$query->join('JOIN %1t %2t ON %2t.%3t = %4t.%5t', $this->tableName, $alias, $this->hasManyConfig[$name]['local'], $joinAlias, $this->hasManyConfig[$name]['table']['local']);
			} else {
				$query->join('JOIN %1t %2t ON %2t.%3t = %4t.%5t', $this->tableName, $alias, $this->hasManyConfig[$name]['local'], 't1', $this->hasManyConfig[$name]['foreign']);
			}
			$query->where('%t.%t = %', $alias, $this->pkColumn, $this->data[$this->pkColumn]);
			if (isset($this->hasManyConfig[$name]['where']))
				$query->where($this->hasManyConfig[$name]['where']);
			return $query;
		} else {
			foreach ($this->plugins as $plugin) {
				if (is_callable(array($plugin, $name)))
					return call_user_func_array(array($plugin, $name), $args);
			}
			throw new RecordException('Tried to call a unknown function on "'.get_class($this).'" "'.$name.'"');
		}
	}

	public function registerPlugin($plugin, $options = array()) {
		$classname = $plugin.'Plugin';
		require_once dirname(__FILE__).'/plugins/'.$classname.'.class.php';
		$class = new $classname($this, $options);
		$this->plugins[] = $class;
	}

	public function notifyListeners($method, $event) {
		if (substr($method, 0, 4) == 'post') {
			foreach (array_reverse($this->plugins) as $plugin)
				$plugin->$method($event);
			$this->$method($event);
		} else {
			$this->$method($event);
			foreach ($this->plugins as $plugin)
				$plugin->$method($event);
		}
	}

	public function toArray($withHasOne = self::LOAD_DEFAULT, $prefix = '') {
		$result = array();
		foreach ($this->config as $name => $value) {
			if (!$this->getPk()) {
				$result[$prefix.$name] = isset($this->data[$name]) ? $this->data[$name] : null;
			} elseif (is_object($this->{$name})) {
				$result[$prefix.$name] = isset($this->data[$name]) ? $this->data[$name] : null;
			} else {
				$result[$prefix.$name] = $this->{$name};
			}
		}

		if ($withHasOne !== self::LOAD_NONE) {
			foreach ($this->hasOneConfig as $key => $value) {
				if (!$this->getPk()) {
					$result += (isset($this->hasOneData[$key]) ? $this->hasOneData[$key]->toArray(self::LOAD_NONE, $prefix.$key.self::RELATION_SEPARATOR) : array());
				} else {
					$aliasIndex = 0;
					if ( (isset($value['loading']) && $value['loading'] == self::PASSTHROUGH) ) continue; //skip passthrough
					if ( (isset($value['loading']) && $value['loading'] == self::GREEDY) || $withHasOne === self::LOAD_ALL) {
						$obj = $this->__get($key);
						if ($obj !== null) {
							$result += $obj->toArray(self::LOAD_NONE, $prefix.$key.self::RELATION_SEPARATOR);
						}
					}
				}
			}
		}
		return $result;
	}

	protected $softKeyDefinition = 'id';

	public function loadBySoftKey($key) {
		$rows = $this->db->query('SELECT id FROM %t WHERE %l = % LIMIT 2', $this->tableName, $this->softKeyDefinition, $key)->fetchAllCells();
		if (!count($rows))
			throw new RecordException('Soft key \''.$key.'\' in record '.get_class($this).' not found');
		elseif (count($rows) > 1)
			throw new RecordException('Soft key \''.$key.'\' in record '.get_class($this).' not unique');
		$this->load($rows[0]);
	}
	
	public function softKey() {
		return $this->db->query('SELECT %l FROM %t WHERE id = %', $this->softKeyDefinition, $this->tableName, $this->id)->fetchCell();
	}
	
	public function has($key, $config) { $this->config[$key] = $config; }
	public function hasOne($key, $config) { $this->hasOneConfig[$key] = $config; }
	public function hasMany($key, $config) { $this->hasManyConfig[$key] = $config; }
	
	public function getHasManyConfig($key) { return @$this->hasManyConfig[$key]; }
	public function getHasOneConfig($key) { return @$this->hasOneConfig[$key]; }
	
	/* Get an instance of a record object by name */
	public static function getInstance($name) {
		$name = ucfirst(strtolower($name));
		include_once($name.'.class.php');
		if (!class_exists($name, false))
			throw new RecordException('Unknown record class '.$name);
		$obj = new $name();
		if (!($obj instanceof Record))
			throw new RecordException('Unknown record class '.$name);
		return $obj;
	}
}