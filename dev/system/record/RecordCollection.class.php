<?php
//TODO: handle non persistated objects!

require_once 'record/RecordBase.abstract.php';

class RecordCollection extends RecordBase implements IteratorAggregate, ArrayAccess, Countable {

	protected $className;
	protected $elements = array();
	protected $queryData = array();

	public function __construct($className) {
		$this->className = $className;
	}

	public function __destruct() {
		$this->clear();
		if ($this->parent)
			unset($this->parent);
	}

	public function __sleep() {
		return array('className', 'elements', 'queryData');
	}

	public function fetch($db, $queryData) {
		$this->queryData = $queryData;
		$rec = new $this->className();
		$query = $rec->buildFetchQuery($queryData);
		
		$q = $db->query($query);
		while ($row = $q->fetchRow()) {
			$obj = new $this->className();
			$obj->loadFromArray($row);
			$obj->parent = $this;
			$this->elements[(string)$obj->getPk()] = $obj;
		}
		$this->dirty = false;
	}
	
	public function getClassName() {
		return $this->className;	
	}

	protected function buildQuery($query, $n, $conf, array $returnPath, Database $db) {		
		if ($this->parent) {
			return $this->parent->buildQuery($query, $n, $conf, $returnPath, $db);
		} else {
			if ($this->queryData['limitCount']) {
				$obj = new $this->className();				
				$alias = 't'.($n-1);
				$query->where('%t.%t IN (%l)', $alias, $obj->getPkColumn(), implode(', ', array_keys($this->elements)));
			} else {
				$diff = false;
				$originalJoin = implode(' ', $this->queryData['join']);
				$join = '';
				if ($originalJoin) {					
					$parts = preg_split('/(\'(?:\\\\.|[^\'])*\')/', $originalJoin, -1, PREG_SPLIT_DELIM_CAPTURE);
					$inString = false;
					foreach ($parts as $part) {												
						if (!$inString && preg_match_all('/\bt(\d*)\b/e', $part, $matches)) {
							$value = (int)max($matches[1]);
							$diff = ($n - $value);							
							$join .= preg_replace('/\bt(\d*)\b/e', '"t".($1 + '.$diff.')', $part);														
						}
						else
							$join.= $part;
						$inString = !$inString;
					}					
				}
				$originalWhere = implode(' AND ', $this->queryData['where']);
				$where = '';					
				if ($originalWhere) {
					$parts = preg_split('/(\'(?:\\\\.|[^\'])*\')/', $originalWhere, -1, PREG_SPLIT_DELIM_CAPTURE);
					$inString = false;
					foreach ($parts as $part) {
						if (!$inString && preg_match_all('/\bt(\d*)\b/e', $part, $matches)) {
							if (!$diff) {
								$value = (int)max($matches[1]);
								$diff = ($n - $value);							
							}
							$where.= preg_replace('/\bt(\d*)\b/e', '"t".($1 + '.$diff.')', $part);
						}
						else
							$where.= $part;
						$inString = !$inString;
					}
				}
				if ($join)
					$query->join($join);
				if ($where)
					$query->where($where);
			}			
			$rows = $query->getStatement()->fetchAllRows(false, '_groupid');
			$this->propagateResults($rows, $returnPath);
		}
	}	
	
	protected function propagateResults($rows, $returnPath) {
		foreach (array_values($this->elements) as $el) {// array_values is used to avoid resetting the array
			$el->propagateResults($rows, $returnPath);
		}
	}

	//@OVERRIDE
	protected function save() {
		foreach ($this->elements as $el) {
			$el->save();
		}
		$this->dirty = false;
	}

	//TODO:
	// We can add() objects before they're saved. At this point their array index will
	// be undefined. (use false or something when iterating over them? How can we access them
	// by direct index?)
	// Also need some checking on a possible relation where clause, do we update this record to match the clause or do we ignore it?
	public function add($obj) {
		if (get_class($obj) != $this->className) return;
		
		$obj->parent = $this;
		$obj->parentConfig = $this->parentConfig;
		$this->elements[(string)$obj->getPk()] = $obj;
		$this->dirty = true;
		if (isset($this->parent))
			$this->parent->dirty = true;
	}

	//TODO:
	// I think it may be better to use Collection[]->delete(). Can be wrong about that one
	// though, what is most practical will be determined while writing the class.
	public function remove($obj) {
		if (get_class($obj) != $this->className) return;
		if (!isset($this->elements[(string)$obj->getPk()])) return;
		$obj->parent = null;
		$obj->parentConfig = null;
		$this->elements[(string)$obj->getPk()]->__destruct(); //free memory !
		unset($this->elements[(string)$obj->getPk()]);		
		$this->dirty = true;
		if ($this->parent)
			$this->parent->dirty = true;
	}

	public function clear() {
		foreach($this->elements as $key => &$el) {
			$el->__destruct();
		}
		$this->elements = array();
		$this->dirty = true;
		if ($this->parent)
			$this->parent->dirty = true;
	}

	public function replace(RecordCollection $src) {
		if ($src->getClassName() != $this->className) return;
		$this->elements = $src->elements;
		$this->dirty = true;
		if ($this->parent)
			$this->parent->dirty = true;
	}

	public function merge(RecordCollection $src) {
		if ($src->getClassName() != $this->className) return;
		$this->elements = $this->elements + $src->elements; //union, no elements with the same key are overwritten
		$this->dirty = true;
		if ($this->parent)
			$this->parent->dirty = true;
	}

	// IteratorAggregate
	// Need to figure out if we can use a pre-built iterator, or if we need a custom one.
	public function getIterator() {
		return new ArrayIterator($this->elements);
	}

	// ArrayAccess
	//NOTE: The keys are the id's, so you can do a numeric loop to get them, use the iterator instead
	public function offsetExists($offset) {
		return isset($this->elements[$offset]);
	}

	public function offsetGet($offset) {
		if ($this->offsetExists($offset)) {
			$element = $this->elements[$offset];			
			$element->parent = null;
			return $element;
		} else {
			trigger_error('Undefined offset: '. $offset .' in RecordCollection', E_USER_NOTICE);
			return null;
		}
	}

	public function offsetSet($offset, $value) { // Triggers an error
		trigger_error('Can\'t set element : '.$offset.' = '.$value.' in RecordCollection', E_USER_NOTICE);
	}

	// Another problem with offsetUnset has to do with my comment at ::add
	public function offsetUnset($offset) { // Trigger error, see ::remove
		trigger_error('Can\'t unset element : '.$offset.' in RecordCollection', E_USER_NOTICE);
	}

	// Countable
	public function count() {
		return count($this->elements);
	}

	public function first() {
		$element = reset($this->elements);
		if ($element) {
			$element->parent = null;
		}
		return $element;
	}
	
	public function toArray() {
		return $this->elements;
	}
}