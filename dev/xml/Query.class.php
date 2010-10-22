<?php

/*
	Interface for callable Query objects
*/
interface Query {
	/* Convert the Query object to pseudo-SQL */
	public function toSql();
	
	/* Execute the query */
	public function execute();
}

/*
	A wrapper for an array of queries.
*/
class CompositeQuery implements Query {
	public $queries = array();
	
	/* Iterate over the queries and return their sql representations */
	public function toSql() {
		$result = '';
		foreach ($this->queries as $query)
			$result .= $query->toSql().';'."\n";
		return $result;
	}
	
	/* Execute all queries */
	public function execute() {
		//var_dump($this->toSql());
		$result = array();
		foreach ($this->queries as $query)
			$result = array_merge($result, $query->execute());
		return $result;
	}
	
	/* Add a query to the composite */
	public function add($query) {
		$this->queries[] = $query;
	}
	
	/* Merge two composites */
	public function merge($composite) {
		$this->queries = array_merge($this->queries, $composite->queries);
	}
}

/*
	A query object implementing a select call
*/
class SelectQuery implements Query {
	public $select = array();
	public $from = null;
	public $where = array();
	public $order = array();
	public $limit = 50;
	public $offset = 0;
	
	protected $aliases = array();

	public function toSql() {
		/* SQLize select, from, where and order clauses */
		/*
		$selectSql = $this->select == '*' ? 'SELECT *' :
			'SELECT '.implode(', ', array_map(create_function('$k,$v', 'return $v." AS ".$k;'), array_keys($this->select), $this->select));
		$fromSql = 'FROM '.$this->from;
		$whereSql = $this->getWhereClause();
		$orderSql = $this->order ? 'ORDER BY '.implode(', ', $this->order) : '';
		
		$groupBy = $this->getGroupBy();
		$groupBySql = $groupBy ? 'GROUP BY '.implode(', ', $groupBy) : '';
		
		return implode(' ', array_filter(array($selectSql, $fromSql, $whereSql, $groupBySql, $orderSql)));
		*/
		return $this->getRecordQuery()->getStatement();
	}
	
	public function execute() {
		return $this->getRecordQuery()->get();
	}
	
	protected function alias($obj) {
		return is_object($obj) ? $obj->alias($this->aliases) : $obj;
	}
	
	public function getRecordQuery() {
		$query = Record::getInstance($this->from)->select()->setFetchMode(RecordQuery::FETCH_ARRAY);
		$this->aliases = array();
		foreach ($this->getJoin() as $join => $condition) {
			$alias = $this->aliases[$join] = 't'.(count($aliases) + 1);
			$query = $query->join('JOIN %t %t ON %l', $join, $alias, $condition);
		}
		if ($this->select != '*') {
			$query = $query->addRecordColumns(false);
			foreach ($this->select as $key => $value)
				$query = $query->addExtraColumn('%l AS %t', $this->replaceAliases($value), $key);
		}
		foreach ($this->where as $where)
			$query = $query->where('%l', $this->alias(where));
		foreach ($this->order as $order)
			$query = $query->order('%l', $this->alias(order));
		foreach ($this->getGroupBy() as $group)
			$query = $query->group('%l', $this->alias(group));
		$query = $query->limit($this->limit, $this->offset);
		
		return $query;
	}
		
	protected function getGroupBy() {
		if ($this->select == '*')
			return array();
		
		$groupBy = array();
		
		/* Find all properties we use, and all properties used in any aggregates */
		$aggregateProperties = $selectProperties = array();
		foreach ($this->select as $item) {
			$selectProperties = array_merge($selectProperties, $item->propertyList());
			$aggregateProperties = array_merge($aggregateProperties, $item->aggregatePropertyList());
		}
		$aggregateProperties = array_unique($aggregateProperties);
		$selectProperties = array_unique($selectProperties);
		
		/* If we use aggregates, add all properties we use but aren't in any aggregate to the group by clause */
		if ($aggregateProperties) {
			foreach ($selectProperties as $prop)
				if (!in_array($prop, $aggregateProperties))
					$groupBy[] = '"'.$prop.'"';
		}
		
		return $groupBy;
	}
	
	protected function getJoin() {
		/* Find all compound properties */
		$compoundProperties = array();
		foreach (array($this->select != '*' ? $this->select : array(), $this->where, $this->order) as $array)
			foreach ($array as $item)
				$compoundProperties = array_merge($selectProperties, $item->compoundPropertyList());
		$compoundProperties = array_map();
		$compoundProperties = array_unique($compoundProperties);
	}
	
	public function getWhereClause() {
		return $this->where ? 'WHERE ('.implode(') AND (', $this->where).')' : '';
	}
}

/*
	A query object implementing an insert call.
	Can either be an explicit insert or an insert-select.
*/
class InsertQuery implements Query {
	public $table = null;
	public $fields = array();
	public $query = null;
	
	/* The id assigned to the row after insertion (only if it is an explicit insert).
	   This value is used by reference in other queries so their ids are updated when this
	   row is inserted. */
	public $id = null;
	
	protected static $next = 0;

	public function toSql() {
		$this->id = ++self::$next;
		$fields = $this->query ? $this->query->select : $this->fields;
		return 'INSERT INTO '.$this->table.' '.(is_array($fields) ? '("'.implode('", "', array_keys($fields)).'") ' : '').
			($this->query ? $this->query->toSql() : ' VALUES ('.implode(', ', $this->fields).')').' ==> '.$this->id;
	}

	public function execute() {
		$data = $this->query ? $this->query->execute() : array(array_map(array($this, 'unwrapValue'), $this->fields));
		$result = array();
		foreach ($data as $row) {
			/* If the table name contains an underscore, it refers to a foreign relation
			   The first part will be the parent table, the second part the name of the relation in that table */
			list($table, $subtable) = strpos($this->table, '_') !== false ? explode('_', $this->table, 2) : array($this->table, null);
			if ($subtable) {
				/* Check if there's a one-to-many or many-to-many relation */
				$manyConfig = Record::getInstance($table)->getHasManyConfig($subtable);
				if ($manyConfig) {
					/* There is, check if there's a many-to-many relation */
					if (array_key_exists('table', $manyConfig))
						/* It is, so we're inserting a row in the junction table */
						$obj = Record::getInstance($manyConfig['table']['class']);
					else {
						/* It's not, so we're updating the foreign object */
						$field = $manyConfig['foreign'];
						$obj = Record::getInstance($manyConfig['class']);
						$obj->load($row['foreign_id']);
						$obj->$field = $row['local_id'];
						$obj->save();
						continue;
					}
				} else {
					//var_dump($row, $this->toSql());
					/* There's not, so we're updating the local object */
					$oneConfig = Record::getInstance($table)->getHasOneConfig($subtable);
					$field = $oneConfig['local'];
					$obj = Record::getInstance($table);
					//var_dump(get_class($obj));
					$obj->load($row['local_id']);
					$obj->$field = $row['foreign_id'];
					$obj->save();
					continue;
				}
			} else {
				$obj = Record::getInstance($table);
				$mmConfig = false;
			}
			foreach ($row as $key => $val) {
				/* Rename local and foreign keys for many-to-many relations */
				if ($key == 'local_id' && $manyConfig) $key = $manyConfig['table']['local'];
				if ($key == 'foreign_id' && $manyConfig) $key = $manyConfig['table']['foreign'];
				$obj->$key = $val;
			}
			$obj->save();
			if (!$this->query)
				$this->id = $obj->id;
			$result[] = array(strtolower(get_class($obj)), array('id' => $obj->id), $obj->softKey());
			//var_dump(array($table, array('id' => $obj->id)));
		}
		return $result;
	}
	
	protected function unwrapValue($v) {
		$v = is_object($v) ? $v->__toString() : $v;
		if (preg_match('/^\'(.+)\'$/', $v, $match))
			return str_replace('\\\'', '\'', $match[1]);
		else
			return $v;
	}
}

/*
	A query object implementing an update call
*/
class UpdateQuery implements Query {
	public $table = null;
	public $fields = array();
	public $where = array();
	
	public function toSql() {
		return 'UPDATE '.$this->table.' SET '.
			implode(', ', array_map(create_function('$k,$v', 'return "\"".$k."\" = ".$v;'), array_keys($this->fields), $this->fields)).
			' WHERE ('.implode(') AND (', $this->where).')';
	}
	
	public function execute() {
		$select = new SelectQuery();
		$select->select = array('id' => 'id') + $this->fields;
		$select->from = $this->table;
		$select->where = $this->where;
		$data = $select->execute();
		if (!$data) return;
		
		$data = array_combine(
			array_map(create_function('$a', 'return (int) $a["id"];'), $data),
			array_map(create_function('$a', 'unset($a["id"]); return $a;'), $data)
		);
		
		$obj = Record::getInstance($this->table);
		$list = $obj->select()->where('id IN (%l)', implode(', ', array_keys($data)))->get();
		foreach($list as $obj) {
			foreach($data as $key => $val)
				$obj->$key = $val;
			$obj->save();
		}
	}
}

/*
	A query object implementing an delete call
*/
class DeleteQuery implements Query {
	public $table = null;
	public $where = array();
	public $subqueries = array();
	
	public function toSql() {
		return 'DELETE FROM '.$this->table.' WHERE ('.implode(') AND (', $this->where).')'.
			implode('', array_map(create_function('$k,$q', 'return " AND \"".$k."\" IN (".$q->toSql().")";'), array_keys($this->subqueries), $this->subqueries));
	}
	
	public function execute() {
		$obj = Record::getInstance($this->table);
		$select = new SelectQuery();
		$select->from = $this->table;
		$select->where = $this->where;
		
		$query = $obj->select();
		foreach ($this->subqueries as $key => $subquery)
			$query->where('%t IN (%l)', $key, $subquery->toSql());
		$list = $query->get();
		
		foreach ($list as $obj)
			$obj->delete();
	}
}

?>