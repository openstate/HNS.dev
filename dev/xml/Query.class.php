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

	public function toSql() {
		/* SQLize select, from, where and order clauses */
		$selectSql = $this->select == '*' ? 'SELECT *' :
			'SELECT '.implode(', ', array_map(create_function('$k,$v', 'return $v." AS ".$k;'), array_keys($this->select), $this->select));
		$fromSql = 'FROM '.$this->from;
		$whereSql = $this->where ? 'WHERE ('.implode(') AND (', $this->where).')' : '';
		$orderSql = $this->order ? 'ORDER BY '.implode(', ', $this->order) : '';
		
		if (is_array($this->select)) {
			$groupBySql = array();
			
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
						$groupBySql[] = '"'.$prop.'"';
			}
			$groupBySql = $groupBySql ? 'GROUP BY '.implode(', ', $groupBySql) : '';
		} else
			$groupBySql = '';
		
		return implode(' ', array_filter(array($selectSql, $fromSql, $whereSql, $groupBySql, $orderSql)));
	}
	
	public function execute() { }
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

	public function execute() { }
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
	
	public function execute() { }
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
	
	public function execute() { }
}

?>