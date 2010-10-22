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
	
	protected $recordQuery = null;
	protected $joins = array();
	protected $groupBy = array();
	protected $tableOrders = array();
	protected $compoundSelect = array();

	public function toSql() {
		return $this->getRecordQuery()->setFetchMode(RecordQuery::FETCH_SQL)->get();
	}
	
	public function execute() {
		$raw = array_map(create_function('$a', 'return array("'.addslashes($this->from).'", array_filter($a, "is_scalar"));'), $this->getRecordQuery()->get());
		$result = array();
		
		/* Parse the raw query data to a tree structure */
		foreach ($raw as $item) {
			/* If necessary, create the root node for this entry */
			if (!array_key_exists($objId = $item[1]['id'], $result))
				$result[$objId] = array($item[0], array());
				
			/* Parse the returned values */
			foreach ($item[1] as $key => $value) {
				$parts = explode('::', $key);
				/* This is an id value, only include if it can be used to access the object (ie, not for subquery joins) */
				if ($parts[count($parts)-1] == 'id') {
					$table = @$this->joins[implode('::', array_slice($parts, 0, -1))][0];
					if ($table && !is_object($table)) continue;
				}
				/* Get a reference to the root node */
				$ref = &$result[$objId][1];
				$length = 0;
				for ($i = 0; $i < count($parts)-1; $i++) {
					/* Find the shortest prefix which has an id associated with it */
					$length++;
					$part = implode('_', array_slice($parts, $i-$length+1, $length));
					$prefix = implode('::', array_slice($parts, 0, $i+1));
					
					/* Try to find the id */
					$id = array_key_exists($idKey = $prefix.'::id', $item[1]) ? $item[1][$idKey] : false;
					
					/* No id found, try longer prefix */
					if ($id === false) continue;
					
					/* Id found, reset length for next iteration */
					$length = 0;
					
					/* Create a tag for the structure if necessary */
					$structKey = array_key_exists($part, $this->compoundSelect) ? $this->compoundSelect[$part] : $part;
					if (!array_key_exists($structKey, $ref)) $ref[$structKey] = array();
					
					/* Create a subtag based on the type of table or the tag name defined by the subquery */
					$table = $this->joins[$prefix][0];
					if (!array_key_exists($id, $ref[$structKey]))
						$ref[$structKey][$id] = array(is_object($table) ? strtolower(get_class($table)) : $table[1], array());
						
					/* Get a reference to the new node */
					$ref = &$ref[$structKey][$id][1];
				}
				/* Set the value */
				$ref[$parts[count($parts)-1]] = $value;
				unset($ref);
			}
		}
	
		return array_merge(array(array('sql', array(), $this->toSql())), $result);
	}
/*	
	protected function alias($obj) {
		return is_object($obj) ? $obj->alias($this->aliases) : $obj;
	}
*/
	public function getRecordQuery() {
		if ($this->recordQuery) return $this->recordQuery;
		
		/* Create query object */
		$query = ApiRecord::getInstance($this->from)->select()->setFetchMode(RecordQuery::FETCH_ASSOC);
		
		/* Replace compound selects */
		$this->replaceSelectRelations();
		
		/* Find necessary joins */
		$this->joins = $this->getJoin();
		
		/* Handle select all */
		if ($this->select == '*') {
			$this->select = array();
			$obj = ApiRecord::getInstance($this->from);
			foreach ($obj->getSelectAll() as $prop)
				$this->select[] = new QueryProperty($prop);
		}
		
		/* Always select primary key */
		$this->select['id'] = new QueryProperty('id');
		$query = $query->addRecordColumns(false);
	
		/* Store table instances */
		$tableInstances = array_merge(
			array('' => ApiRecord::getInstance($this->from)),
			array_filter(array_map('reset', $this->joins), 'is_object')
		);
		
		/* Add joins and orders necessary for where clauses */
		$extraJoins = array();
		$extraOrders = array();
		foreach ($this->where as $where) {
			$where->renderWithQueries($tableInstances);
			$extraJoins = array_merge($extraJoins, $where->joinClauses($this->from, $this->joins));
			$extraOrders = array_merge($extraOrders, $where->orderClauses($this->order));
		}
		$this->joins = array_merge($this->joins, $extraJoins);
		$this->order = array_merge($this->order, $extraOrders);
	
		/* Add joins to query */
		foreach ($this->joins as $alias => $join) {
			@list($table, $condition, $aliasParams) = $join;
			$query = $query->join(
				'LEFT JOIN '.(is_object($table) ? '%t' : '%l').' AS %t%l ON %l',
				is_object($table) ? $table->getTableName() : $table[0], $alias, (string)$aliasParams, $condition
			);
		}
		
		/* Add selects to query */
		foreach ($this->select as $key => $value) {
			/* Selects added by replaceSelectRelations have a numeric key */
			if (is_int($key)) $key = reset(array_reverse(explode('.', $value->property)));
			
			/* Map select to its containing table */
			$table = $value->containingTable();
			
			/* Find extra selects and replace lookups / relations */
			$extra = $value->renderWithQueries($tableInstances);
			$query = $query->addExtraColumn('%l AS %t', $value, ($table ? $table.'::' : '').$key);
			
			/* Add extra selects */
			if ($extra)
				foreach ($extra as $subkey => $value)
					$query = $query->addExtraColumn('%l AS %t', $value, ($table ? $table.'::' : '').$key.'/'.$subkey);
		}
			
		/* Find all tables referred to in selects */
		$tables = array();
		foreach ($this->select as $value)
			$tables[] = $value->containingTable();
			
		/* Add table ids to select and add any orders imposed on tables */
		foreach (array_unique(array_filter($tables)) as $table) {
			$prop = new QueryProperty(str_replace('::', '.', $table).'.id');
			$this->select[] = $prop;
			$query = $query->addExtraColumn('%l AS %t', $prop, $table.'::id');
			if (array_key_exists($table, $this->tableOrders))
				$this->order[] = $this->tableOrders[$table];
		}
		
		/* Find group by clause */
		$this->groupBy = $this->getGroupBy();
		
		/* Add where clause */
		foreach ($this->where as $where)
			$query = $query->where('%l', $where);
			
		/* Add order clause, remove order clauses that depend on properties used in aggregates */
		foreach ($this->order as $order)
			if (!array_intersect($order->propertyList(), $this->aggregateProperties)) {
				$order->renderWithLiterals(array_keys($this->select));
				$order->renderWithQueries($tableInstances);
				$query = $query->order('%l', $order);
			}

		/* Add group by clause */
		foreach ($this->groupBy as $group)
			$query = $query->group('%l', $group);
			
		/* Add limit and offset clause */
		$query = $query->limit($this->limit, $this->offset);
		
		return $this->recordQuery = $query;
	}
		
	protected function getGroupBy() {
		if ($this->select == '*')
			return array();
		
		/* Find all properties we use inside and outside any aggregates */
		$aggregateProperties = $nonAggregateProperties = array();
		foreach ($this->select as $item) {
			$nonAggregateProperties = array_merge($nonAggregateProperties, $item->nonAggregatePropertyList());
			$aggregateProperties = array_merge($aggregateProperties, $item->aggregatePropertyList());
		}
		$this->aggregateProperties = $aggregateProperties = array_unique($aggregateProperties);
		$nonAggregateProperties = array_unique($nonAggregateProperties);
		
		/* Check we're not using properties both inside and outside of aggregates */
		if ($intersect = array_intersect($aggregateProperties, $nonAggregateProperties))
			throw new ParseException('Property '.reset($intersect)->property.' used both inside and outside of aggregates');
		
		/* If we use aggregates, return the properties not used in aggregate functions */
		return $aggregateProperties ? $nonAggregateProperties : array();
	}
	
	protected function getJoin() {
		/* Find all table references and sort them */
		$tables = array();
		foreach (array($this->select != '*' ? $this->select : array(), $this->where, $this->order) as $array)
			foreach ($array as $item)
				$tables = array_merge($tables, $item->tableReferenceList());
		if (!$tables) return array();
		$tables = array_unique($tables);
		sort($tables);
		$tables = array_reverse($tables);
		
		$joins = array();
		foreach ($tables as $table) {
			/* Find table parts  and verify it's not a part of the join yet */
			$table = explode('::', $table);
			if (!array_key_exists(implode('::', $table), $joins)) {
				/* Get object for query table */
				$obj = ApiRecord::getInstance($this->from);
				$this->tableInstances[''] = $obj;
				$alias = '';
				/* Iterate over table prefixes */
				for ($i = 0; $i < count($table); $i++) {
					$oldAlias = $alias;
					/* Create alias for new table */
					$alias = $alias.($alias ? '::' : '').$table[$i];
					$oldObj = $obj;
					$manyConfig = $obj->getHasManyConfig($table[$i]);
					if ($manyConfig) {
						/* Has many type relation */
						if ($junction = @$manyConfig['table']) {
							/* Join junction table */
							$obj = ApiRecord::getInstance($junction['class']);
							$junctionAlias = 'junction:'.$alias;
							$joins[$junctionAlias] = array(
								$obj,
								($oldAlias ? '"'.$oldAlias.'"' : 't1').'."'.$manyConfig['local'].
								'" = "'.$junctionAlias.'"."'.$junction['local'].'"'
							);
							
							/* Join relation table */
							$obj = ApiRecord::getInstance($manyConfig['class']);
							$joins[$alias] = array(
								$obj,
								'"'.$junctionAlias.'"."'.$junction['foreign'].
								'" = "'.$alias.'"."'.$manyConfig['foreign'].'"'
							);
						} elseif ($subquery = @$manyConfig['query']) {
							/* Join is defined with subquery, join it */
							$joins[$alias] = array(
								array('('.$subquery.')', $manyConfig['xml_tag']),
								($oldAlias ? '"'.$oldAlias.'"' : 't1').'."'.$manyConfig['local'].
								'" = "'.$alias.'"."'.$manyConfig['foreign'].'"'
							);
						} else {
							/* Many-to-one relation, join it */
							$obj = ApiRecord::getInstance($manyConfig['class']);
							$joins[$alias] = array(
								$obj,
								($oldAlias ? '"'.$oldAlias.'"' : 't1').'."'.$manyConfig['local'].
								'" = "'.$alias.'"."'.$manyConfig['foreign'].'"'
							);
						}
						/* If the join imposes an order, associate it with the table */
						if ($order = @$manyConfig['order']) {
							$direction = @$manyConfig['direction'];
							if (!$direction) $direction = 'asc';	
							$this->tableOrders[$alias] = new QueryOrder(
								new QueryProperty(implode('.', array_slice($table, 0, $i+1)).'.'.$order),
								$direction
							);
						}
					} else {
						$oneConfig = $obj->getHasOneConfig($table[$i]);
						if (!$oneConfig)
							/* Relation doesn't exist */
							throw new ParseException('No relation configuration found for '.$obj->getClassName().'.'.$table[i]);
						/* Has one type relation, join it */
						$obj = ApiRecord::getInstance($oneConfig['class']);
						$joins[$alias] = array(
							$obj,
							($oldAlias ? '"'.$oldAlias.'"' : 't1').'."'.$oneConfig['local'].
								'" = "'.$alias.'"."'.$oneConfig['foreign'].'"'
						);
					}
				}
			}
		}
		return $joins;
	}

	/* Replace relations in select with their constituent fields */
	protected function replaceSelectRelations() {
		if ($this->select == '*')
			return;
		
		/* Find tables referred to in the select clauses */
		$tables = array();
		foreach ($this->select as $item)
			$tables = array_merge($tables, $item->tableReferenceList());
		$tables[] = null;
		$tables = array_unique($tables);
		sort($tables);
		$tables = array_reverse($tables);
		
		foreach ($tables as $table) {
			/* Get object for query table */
			$obj = ApiRecord::getInstance($this->from);
			$table = $table ? explode('::', $table) : array();
			
			/* Iterate over table parts to find object representing table */
			for ($i = 0; $i < count($table); $i++) {
				$manyConfig = $obj->getHasManyConfig($table[$i]);
				$oneConfig = $obj->getHasOneConfig($table[$i]);
				$obj = ApiRecord::getInstance($manyConfig ? $manyConfig['class'] : $oneConfig['class']);
			}
			
			/* Iterate over select clauses to find unqualified relations */
			$prefix = implode('::', $table);
			foreach ($this->select as $key => $select) {
				/* Only consider query properties */
				if (!($select instanceof QueryProperty))
					continue;
				$parts = explode('.', $select->property);
				$last = array_pop($parts);
				
				/* This property refers to the current table */
				if (implode('::', $parts) == $prefix) {
					$properties = array();
					if (($rel = $obj->getHasManyConfig($last)) && $rel && array_key_exists('select_all', $rel)) {
						/* The property is a has many relation with explicit select all field (probably a subquery) */
						$properties = $rel['select_all'];
					} elseif ($rel) {
						/* The property is a has many relation with a remote class, use that class's getSelectAll method */
						$obj = ApiRecord::getInstance($rel['class']);
						$properties = $obj->getSelectAll();
					}
					if (!$properties) continue;
					
					/* Properties found, generate select clauses */
					foreach ($properties as $prop)
						$this->select[] = new QueryProperty($select->property.'.'.$prop);

					/* Remove compound clause but add it to rename the xml tag to the given alias */
					unset($this->select[$key]);
					
					if ($key != $last)
						$this->compoundSelect[str_replace('.', '_', $select->property)] = $key;
				}
			}
		}
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
		$data = $this->query ? $this->query->getRecordQuery()->get() : array(array_map('unwrapValue', $this->fields));
		$result = array();
		foreach ($data as $row) {
			/* If the table name contains colons, it refers to a foreign relation
			   The first part will be the parent table, the second part the name of the relation in that table */
			list($table, $subtable) = strpos($this->table, '::') !== false ? explode('::', $this->table, 2) : array($this->table, null);
			if ($subtable) {
				/* Check if there's a one-to-many or many-to-many relation */
				$manyConfig = ApiRecord::getInstance($table)->getHasManyConfig($subtable);
				if ($manyConfig) {
					/* There is, check if there's a many-to-many relation */
					if (array_key_exists('table', $manyConfig))
						/* It is, so we're inserting a row in the junction table */
						$obj = ApiRecord::getInstance($manyConfig['table']['class']);
					else {
						/* It's not, so we're updating the foreign object */
						$field = $manyConfig['foreign'];
						$obj = ApiRecord::getInstance($manyConfig['class']);
						$obj->load($row['foreign_id']);
						$obj->$field = $row['local_id'];
						$obj->save();
						continue;
					}
				} else {
					/* There's not, so we're updating the local object */
					$oneConfig = ApiRecord::getInstance($table)->getHasOneConfig($subtable);
					if (!$oneConfig)
						throw new ParseException('No relation '.$subtable.' exists in record '.ucfirst($table));
					$field = $oneConfig['local'];
					$obj = ApiRecord::getInstance($table);
					$obj->load($row['local_id']);
					$obj->$field = $row['foreign_id'];
					$obj->save();
					continue;
				}
			} else {
				$obj = ApiRecord::getInstance($table);
				$manyConfig = false;
			}
			foreach ($row as $key => $val) {
				/* Ignore primary key */
				if ($key == 'id') continue;
				
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
		$select->select = array_filter($this->fields, create_function('$f', 'return !is_string($f);'));
		$select->from = $this->table;
		$select->where = $this->where;
		$data = $select->getRecordQuery()->get();
		foreach ($data as &$row)
			$row = array_merge($row, array_map('unwrapValue', array_filter($this->fields, 'is_string')));
		unset($row);
		if (!$data) return array();
		
		$data = array_combine(
			array_map(create_function('$a', 'return (int) $a["id"];'), $data),
			array_map(create_function('$a', 'unset($a["id"]); return $a;'), $data)
		);
		
		$obj = ApiRecord::getInstance($this->table);
		$list = $obj->select()->where('id IN (%l)', implode(', ', array_keys($data)))->get();
		$result = array();
		foreach($list as $obj) {
			foreach($data[$obj->id] as $key => $val)
				$obj->$key = $val;
			$obj->save();
			$result[] = array(strtolower(get_class($obj)), array('id' => $obj->id), $obj->softKey());
		}
		return $result;
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
		$obj = ApiRecord::getInstance($this->table);
		$select = new SelectQuery();
		$select->from = $this->table;
		$select->where = $this->where;
		
		$query = $select->getRecordQuery();
		foreach ($this->subqueries as $key => $subquery) {
			$subids = array_map(create_function('$x', 'return $x["'.$key.'"];'), $subquery->getRecordQuery()->get());
			if ($subids)
				$query->where('%t IN (%l)', $key, implode(', ', $subids));
			else
				return array();
		}
		$ids = $query->get();
		if (!$ids) return array();
		
		$list = $obj->select()->where('id IN (%l)', implode(', ', array_map('reset', $ids)))->get();
		
		$result = array();
		foreach ($list as $obj) {
			$result[] = array(strtolower(get_class($obj)), array('id' => $obj->id), $obj->softKey());
			$obj->delete();
		}
		return $result;
	}
}

function unwrapValue($v) {
	$v = is_object($v) ? $v->__toString() : $v;
	if (preg_match('/^\'(.+)\'$/', $v, $match))
		return str_replace('\\\'', '\'', $match[1]);
	else
		return $v;
}

?>