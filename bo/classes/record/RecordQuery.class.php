<?php
require_once 'record/RecordCollection.class.php';

class RecordQuery {

	protected $className = null;
	protected $db = null;

	const FETCH_OBJ = 'fetch_object';
	const FETCH_ARRAY = 'fetch_array';
	const FETCH_STATEMENT = 'fetch_statement';

	protected $queryData = array(
		'fetchMode' 	=> self::FETCH_OBJ,
		'recordColumns' => true,
		'extraColumns'	=> array(),
		'alias'			=> 't1',
		'withHasOne'	=> Record::LOAD_DEFAULT,
		'join' 			=> array(),
		'where'			=> array(),
		'order' 		=> array(),
		'limitCount' 	=> false,
		'limitOffset' 	=> false,
		'group' 		=> array(),
		'having' 		=> array()
	);

	public function __construct(Database $db, $className) {
		$this->className = $className;
		$this->db = $db;
	}

	public function __sleep() {
		throw new Exception('Serialization of the RecordQuery isn\'t allowed');
	}

//chain functions

	/*
		Method to exclude the default record columns, will only use the extra columns added with addExtraColumn.
	*/
	public function addRecordColumns($bool) {
		$this->queryData['recordColumns'] = (bool)$bool;
		if (!$bool && $this->queryData['fetchMode'] === self::FETCH_OBJ)
			$this->queryData['fetchMode'] = self::FETCH_STATEMENT;
		return $this;
	}
	/*
		Sets the fetchmode for the get() function, only possible to get an object if you are also getting the record's columns
	*/
	public function setFetchMode($mode) {
		$fetchModes = array(self::FETCH_ARRAY, self::FETCH_STATEMENT);
		if ($this->queryData['recordColumns'] === true) //only allow object fetching if we still get the columns for it.
			$fetchModes[] = self::FETCH_OBJ;
		if (in_array($mode, $fetchModes))
			$this->queryData['fetchMode'] = $mode;
		return $this;
	}
	/*
		Add a join
	*/
	public function join($query) {
		$args = func_get_args();
		$this->queryData['join'][] = call_user_func_array(array($this->db, 'formatQuery'), $args);
		return $this;
	}
	/*
		Add a where
		all where calls will be joined with AND and will have ( ) around them.
		For OR you'll need to do it manually within one call
	*/
	public function where($query) {
		$args = func_get_args();
		$this->queryData['where'][] = call_user_func_array(array($this->db, 'formatQuery'), $args);
		return $this;
	}
	/*
		Adds a order by
		All order by calls will be joined with ', '
	*/
	public function order($query) {
		$args = func_get_args();
		$this->queryData['order'][] = call_user_func_array(array($this->db, 'formatQuery'), $args);
		return $this;
	}
	/*
		Adds a limit (and offset)
		only accepts 2 parameters the first is the count, the second is the offset
	*/
	public function limit($count) {
		$args = func_get_args();
		switch (count($args)) {
			case 1:
				$this->queryData['limitCount'] = $args[0];
				$this->queryData['limitOffset'] = false;
				break;
			case 2:
				$this->queryData['limitCount'] = $args[0];
				$this->queryData['limitOffset'] = $args[1];
				break;
			default:
				$this->queryData['limitCount'] = false;
				$this->queryData['limitOffset'] = false;
		}
		return $this;
	}
	/*
		Adds a group by
		all group by calls are joined with ', '
	*/
	public function group($query) {
		$args = func_get_args();
		$this->queryData['group'][] = call_user_func_array(array($this->db, 'formatQuery'), $args);
		return $this;
	}
	/*
		Adds a having
		all where calls will be joined with AND and will have ( ) around them.
		For OR you'll need to do it manually within one call
	*/
	public function having($query) {
		$args = func_get_args();
		$this->queryData['having'][] = call_user_func_array(array($this->db, 'formatQuery'), $args);
		return $this;
	}
	/*
		Set's the alias to be used for the main table
		Be careful with this.
	*/
	public function setAlias($alias) {
		$this->queryData['alias'] = $alias;
		return $this;
	}
	/*
		Sets if the records should be loaded with the has_one relations
		LOAD_DEFAULT will only load them in configured that way
	*/
	public function withHasOne($withHasOne) {
		if (in_array($withHasOne, array(Record::LOAD_ALL, Record::LOAD_NONE, Record::LOAD_DEFAULT)))
			$this->queryData['withHasOne'] = $withHasOne;
		return $this;
	}
	/*
		Adds an extra column to the select statement
	*/
	public function addExtraColumn($column) {
		$args = func_get_args();
		$this->queryData['extraColumns'][] = call_user_func_array(array($this->db, 'formatQuery'), $args);
		return $this;
	}

//get functions

	public function get() {
		switch	($this->queryData['fetchMode']) {
			case self::FETCH_STATEMENT:
				$rec = new $this->className();
				$query = $rec->buildFetchQuery($this->queryData);
				return $this->db->query($query);
				break;
			case self::FETCH_ARRAY:
				$rec = new $this->className();
				$query = $rec->buildFetchQuery($this->queryData);
				$rows = $this->db->query($query)->fetchAllRows();
				$result = array();
				foreach($rows as $row) {
					$key = array_shift($row);
					$result[$key] = current($row);
				}
				return $result;
				break;
			case self::FETCH_OBJ:
			default:
				$rc = new RecordCollection($this->className);
				$rc->fetch($this->db, $this->queryData);
				return $rc;
				break;
		}
	}

	public function getCountGrouped($groupby = false) {
		$rec = new $this->className();
		$queryData = $this->queryData;
		$queryData['extraColumns'] = array(); //delete any set column
		$queryData['extraColumns'][] = 'COUNT(*) as count';
		$queryData['recordColumns'] = false; //remove record columns

		//people are allowed to add multiple groups in on ->group call, so split them here
		$groups = implode(',', $queryData['group']);
		$groups = explode(',', $groups);
		//add the group by columns
		foreach ($groups as $group) {
			if (empty($group)) continue;
			$queryData['extraColumns'][] = trim($group);
		}
		if ($groupby) {
			if (!is_array($groupby))
				$groupby = array($groupby);
			foreach ($groupby as $group) {
				$queryData['extraColumns'][] = $group;
				$queryData['group'][] = $group;
			}
		}
		$query = $rec->buildFetchQuery($queryData);
		return $this->db->query($query)->fetchAllRows();
	}

	//Returns an number of results in the query
	public function getCount() {
		$rec = new $this->className();
		$queryData = $this->queryData;
		$queryData['extraColumns'] = array(); //delete any set column
		$queryData['extraColumns'][] = 'COUNT(*) as Count';
		$queryData['recordColumns'] = false; //remove record columns
		//notice to make sure it is understood what is happening here.
		if (!empty($queryData['group']))
			trigger_error('Grouping columns are added and will be removed, use getCountGrouped instead if you want them added', E_USER_NOTICE);
		$queryData['group'] = array();
		$query = $rec->buildFetchQuery($queryData);
		return (int)$this->db->query($query)->fetchCell();
	}

	public function getStatement() {
		return $this->setFetchMode(self::FETCH_STATEMENT)->get();
	}
	
	/**
	 * Return the query that will be executed to get the data.
	 *
	 * @return string The query
	 * @author Harro
	 **/
	public function getQuery() {
		$rec = new $this->className();
		return $rec->buildFetchQuery($this->queryData);
	}

}