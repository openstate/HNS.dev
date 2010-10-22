<?php

class RightsException extends Exception { }

/*
	Helper class that creates a filter function based on a number of properties
*/
class RightsFilter {
	public function __construct($filter) {
		$this->filter = $filter;
	}
	
	/* Filter class main function */
	public function call($row) {
		foreach ($this->filter as $key => $val)
			if (!$this->$key($row, $val)) return false;
		return true;
	}

	/* Filter on table (regex match) */
	protected function table($row, $table) {
		return preg_match($row['table'], $table);
	}
	
	/* Filter on property (in_array match) */
	protected function id($row, $id) {
		return $row['ids'] === null || in_array((int) $id, $row['ids'], true);
	}
	
	/* Filter on property (regex match) */
	protected function property($row, $property) {
		return preg_match($row['property'], $property);
	}
	
	/* Filter on property (boolean and match) */
	protected function access($row, $access) {
		return $row['access'] & $access;
	}
	
}

/*
	Rights system. Handles access rights on tables and properties
*/
class Rights {
	protected $tableName = 'usr_rights';
	protected $rights = array();

	const SELECT = 1;
	const INSERT = 2;
	const UPDATE = 4;
	const DELETE = 8;

	/* Fetch the rights for a given user */
	public function __construct($user) {
		$db = DBs::inst(DBs::SYSTEM);
		$this->rights = array_map(array($this, 'formatRights'), $db->query('
			SELECT "table", ids, property, access
			FROM %t WHERE user_id = %',
			$this->tableName, $user->id)->fetchAllRows()
		);
	}
	
	/* Format the rights as returned by the SQL query */
	protected function formatRights($row) {
		return array(
			'table' => $this->formatRegex($row['table']),
			'ids' => $row['ids'] === null ? null : array_map('intval', explode(',', substr($row['ids'], 1, -1))),
			'property' => $this->formatRegex($row['property']),
			'access' => bindec($row['access'])
		);
	}
	
	/* Change a wildcard expression containg * and ? as wildcards to a regular expression */
	protected function formatRegex($s) {
		return '|^'.implode('.*', array_map(array($this, 'formatRegexHelper'), explode('*', $s))).'$|';
	}
	
	protected function formatRegexHelper($s) {
		return implode('.', array_map('preg_quote', explode('?', $s)));
	}
	
	/* Filter the rights list by the given filters */
	protected function filter($filter) {
		return array_filter($this->rights, array(new RightsFilter($filter), 'call'));
	}
	
	/* Check whether the user has any access to the given table */
	public function tableAccess($table, $access) {
		return (bool) $this->filter(array('table' => $table, 'access' => $access));
	}

	/* Check whether the user has any access to the given row */
	public function rowAccess($table, $id, $access) {
		return (bool) $this->filter(array('table' => $table, 'id' => $id, 'access' => $access));
	}

	/* Check whether the user has any access to the given column */
	public function columnAccess($table, $property, $access) {
		return (bool) $this->filter(array('table' => $table, 'property' => $property, 'access' => $access));
	}

	/* Check whether the user has any access to the given cell */
	public function cellAccess($table, $id, $property, $access) {
		return (bool) $this->filter(array('table' => $table, 'id' => $id, 'property' => $property, 'access' => $access));
	}
	
	/* Remove ids the user doesn't have access to from a list ids */
	public function idListFilter($table, $idList, $access) {
		$list = array_map(create_function('$r', 'return $r["ids"];'),
				$this->filter(array('table' => $table, 'access' => $access)));
		$result = array();
		foreach ($list as $l) {
			if ($l === null) return $idList;
			$result = array_merge($result, array_map('intval', explode(',', substr($l, 1, -1))));
		}
		return array_intersect($idList, $result);
	}

	/* Filters a result set as given by the query class to remove values the user doesn't have access to */
	public function resultSetFilter($table, $resultSet, $aliases, $access) {
		global $self;
		$self = $this;
		
		/* Filter out any rows the user doesn't have access to */
		$resultSet = array_filter($resultSet, create_function('$r',
			'global $self; return $self->rowAccess("'.addslashes($table).'", $r["id"], '.(int) $access.');'));
			
		foreach ($resultSet as &$row) {
			/* Filter out subtable rows the user doesn't have access to */
			foreach ($aliases as $alias => $join) {
				if (is_object($join[0]) && (($id = @$row[$alias.'::id']) || ($id = @$row[$alias.'::__hidden_id'])))
					if (!$this->rowAccess($join[0]->getTableName(), $id, $access))
						$row = array_filter($row, create_function('$r',
							'return substr($r, 0, '.(strlen($alias)+2).') != "'.addslashes($alias).'::";'));
			}
			foreach (array_keys($row) as $key) {
				$subtable = $key;
				while (true) {
					/* Split the property and isolate the last part */
					$parts = explode('::', $subtable);
					$property = array_pop($parts);
					$subtable = implode('::', $parts);
					if (strpos($property, '/') !== false) {
						/* The property contains a /, it represents a has-one relation so verify access to the row */
						list($prop, $tbl) = explode('/', $property);
						if ($prop != $tbl && !$this->rowAccess(ApiRecord::getInstance($tbl)->getTableName(), $row[$prop], $access)) {
							unset($row[$property]);
							unset($row[$prop]);
						}
						break;
					}
					
					if ($property == 'id' || $property == '__hidden_id')
						/* Ignore id properties */
						continue;
					
					if (!$subtable) {
						/* We're in the root table, check cell access */
						if (!$this->cellAccess($table, $row['id'], $property, $access))
							unset($row[$key]);
						break;
					} elseif (is_object($obj = $aliases[$subtable][0])) {
						/* We've got a subtable, check access to the cell in that table */
						$id = @$row[$subtable.'::id'];
						if (!$id) $id = $row[$subtable.'::__hidden_id'];
						if (!$this->cellAccess($obj->getTableName(), $id, $property, $access))
							unset($row[$key]);
						break;
					}
				}
			}
		}
		unset($row);
		return $resultSet;
	}
	
}

?>