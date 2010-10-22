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
	protected $queries = array();
	
	/* Iterate over the queries and return their sql representations */
	public function toSql() {
		$result = '';
		foreach ($this->queries as $query)
			$result .= $query->toSql().';'."\n";
		return $result;
	}
	
	/* Execute all queries */
	public function execute() {
		foreach ($this->queries as $query)
			$query->execute();
	}
	
	/* Add a query to the composite */
	public function add($query) {
		$this->queries[] = $query;
	}
	
	/* Merge two composites */
	public function merge($composite) {
		$this->queries = array_merge($this->queries, $composite->queries);
	}
	
	/* Return the last query in this composite */
	public function last() {
		return $this->queries[count($this->queries)-1];
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
		
		return $groupBy
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

	protected static $next = 1;

	public function toSql() {
		$this->id = self::$next++;
		$fields = $this->query ? $this->query->select : $this->fields;
		return 'INSERT INTO '.$this->table.' '.(is_array($fields) ? '("'.implode('", "', array_keys($fields)).'") ' : '').
			($this->query ? $this->query->toSql() : ' VALUES ('.implode(', ', $this->fields).')').' ==> '.$this->id;
	}

	public function execute() {
		$data = $this->query ? $this->query->execute() : array($this->fields);
		foreach ($data as $row) {
			$obj = Record::getInstance($this->table);
			foreach ($row as $key => $val)
				$obj->$key = $val;
			$obj->save();
			if (!$this->query)
				$this->id = $obj->id;
		}
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
		foreach ($select->where('%l', $select->where);
		foreach ($this->subqueries as $key => $subquery)
			$query->where('%t IN (%l)', $key, $subquery->toSql());
		$list = $query->get();
		
		foreach ($list as $obj)
			$obj->delete();
	}
}

?>