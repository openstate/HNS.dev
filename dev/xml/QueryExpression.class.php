<?php

/*
	Interface for query syntax tree elements
*/
interface QueryExpression {
	/* List of properties used in the expression */
	public function propertyList();
	
	/* List of properties used in aggregates in the expression */
	public function aggregatePropertyList();

	/* List of properties used outside aggregates in the expression */
	public function nonAggregatePropertyList();
	
	/* List of tables used in properties used in the expression */
	public function tableReferenceList();
	
	/* Table in which the value is contained, raises an exception if more than one found */
	public function containingTable($inAggregate = false);
	
	/* Set expression to render using literals as given instead of considering them table entries */
	public function renderWithLiterals($literals = array());
	
	/* Set expression to render using column queries defined in ORM objects */
	public function renderWithQueries($tables = array(), $joins = array());
	
	/* Return auxiliary join clauses for this expression */
	public function joinClauses($from, $joins);

	/* Return auxiliary order clauses for this expression */
	public function orderClauses($orders);
}

/*
	Representation of a property in a query
*/
class QueryProperty implements QueryExpression {
	public $property;
	protected $isLiteral = false;
	protected $columnQuery = false;
	
	public function __construct($property) {
		$this->property = $property;
	}
	
	public function __toString() {
		if ($this->columnQuery)
			return $this->columnQuery;
		elseif ($this->isLiteral)
			return '"'.$this->property.'"';
		else {
			$parts = explode('.', $this->property);
			$last = array_pop($parts);
			return '"'.implode('::', $parts).($parts ? '' : 't1').'"."'.$last.'"';
		}
	}
	
	public function propertyList() {
		return array($this);
	}
	
	public function aggregatePropertyList() {
		return array();
	}
	
	public function nonAggregatePropertyList() {
		return array($this);
	}
	
	public function tableReferenceList() {
		$parts = explode('.', $this->property);
		array_pop($parts);
		return $parts ? array(implode('::', $parts)) : array();
	}
	
	public function containingTable($inAggregate = false) {
		$parts = explode('.', $this->property);
		array_pop($parts);
		if ($inAggregate) {
			if ($parts)
				array_pop($parts);
			else
				throw new ParseException('Top-level aggregate not supported');
		}
		return $parts ? implode('::', $parts) : null;
	}
	
	public function renderWithLiterals($literals = array()) {
		$this->isLiteral = in_array($this->property, $literals, true);
	}
	
	public function renderWithQueries($tables = array(), $joins = array()) {
		$parts = explode('.', $this->property);
		$last = array_pop($parts);
		$prefix = implode('::', $parts);
		if (array_key_exists($prefix, $tables)) {
			$val = $tables[$prefix]->getColumnQuery($last, $this->__toString());
			if (is_array($val)) {
				$this->columnQuery = array_shift($val);
				return $val;
			} else if ($val) {
				$this->columnQuery = $val;
				return;
			}
		}
	}
	
	public function joinClauses($from, $joins) {
		return array();
	}

	public function orderClauses($orders) {
		return array();
	}
}

/*
	Representation of a value in a query
*/
class QueryValue implements QueryExpression {
	public $value;
	
	public function __construct($value) {
		$this->value = $value;
	}
	
	public function __toString() {
		return (string) $this->value;
	}
	
	public function propertyList() {
		return array();
	}
	
	public function aggregatePropertyList() {
		return array();
	}
	
	public function nonAggregatePropertyList() {
		return array();
	}
	
	public function tableReferenceList() {
		return array();
	}
	
	public function containingTable($inAggregate = false) {
		return null;
	}

	public function renderWithLiterals($literals = array()) {
		// empty
	}

	public function renderWithQueries($tables = array(), $joins = array()) {
		// empty
	}
	
	public function joinClauses($from, $joins) {
		return array();
	}

	public function orderClauses($orders) {
		return array();
	}
}

/*
	Representation of a function in a query
*/
class QueryFunction implements QueryExpression {
	public $function;
	public $parameters;
	
	protected $matchAlias;
	protected static $nextMatchAliasId = 1;
	protected $tables;
	protected $joins;
	
	public function __construct($function, $params) {
		$this->function = $function;
		$this->parameters = $params;
		
		/* Magical handling for match */
		if ($this->function == 'match')
			$this->matchAlias = 'match:'.self::$nextMatchAliasId++;
		
		/* Magical handling for count */
		if ($this->function == 'count' && $this->parameters[0] instanceof QueryProperty)
			$this->parameters[0] = new QueryProperty($this->parameters[0]->property.'.id');
	}

	protected function and_($params) {
		return '('.implode(' AND ', $params).')';
	}
	
	protected function add($params) {
		return '('.implode(' + ', $params).')';
	}
	
	protected function concat($params) {
		return '('.implode(' || ', $params).')';
	}
	
	protected function count($params) {
		return 'count(DISTINCT '.$params[0].')';
	}
	
	protected function div($params) {
		return '('.implode(' / ', $params).')';
	}
	
	protected function eq($params) {
		return '('.$params[0].' = '.$params[1].')';
	}

	protected function elem($params) {
		$query = new SelectQuery();
		$query->select = array('key' => new QueryProperty($params[2].'.id'));
		$query->from = $params[0];
		$query->where = array(new QueryFunction('eq', array(new QueryProperty('id'), $params[1])));
		$ids = array_map(create_function('$x', 'return $x["'.$params[2].'::key"];'), $query->getRecordQuery()->get());
		if (!$ids) return 'FALSE';
		return 't1.id IN ('.implode(', ', array_map('intval', $ids)).')';
	}
	
	protected function exp($params) {
		return '('.$params[0].' ^ '.$params[1].')';
	}

	protected function ge($params) {
		return '('.$params[0].' <= '.$params[1].')';
	}
	
	protected function gt($params) {
		return '('.$params[0].' > '.$params[1].')';
	}
	
	protected function in($params) {
		if ($params[1] instanceof QueryProperty) {
			$parts = explode('.', $params[1]->property);
			$last = array_pop($parts);
			$prefix = implode('::', $parts);
			if (array_key_exists($prefix, $this->tables)) {
				$many = $this->tables[$prefix]->getHasManyConfig($last);
				if ($many) {
					if ($subquery = @$many['query']) {
						$cond = $many['foreign'].' = '.new QueryProperty(($prefix ? $prefix : 't1').'.'.$many['local']);
						if (strpos($subquery, 'WHERE') === false)
							$subquery = preg_replace('/(?=$| ORDER | GROUP )/', ' WHERE '.$cond, $subquery, 1);
						else
							$subquery = str_replace(' WHERE ', ' WHERE '.$cond.' AND ', $subquery);
						$rhs = preg_replace('/SELECT .*? FROM/', 'SELECT '.$many['soft_key'].' FROM', $subquery);
					} elseif ($junction = @$many['table']) {
						$jnc = ApiRecord::getInstance($junction['class']);
						$obj = ApiRecord::getInstance($many['class']);
						$rhs = 'SELECT '.$obj->getSoftKeyDefinition().' FROM '.$obj->getTableName().' __tbl'.
							' JOIN '.$jnc->getTableName().' __jnc ON __jnc.'.$junction['foreign'].' = __tbl.'.$many['foreign'].
							' WHERE __jnc.'.$junction['local'].' = '.new QueryProperty(($prefix ? $prefix : 't1').'.'.$many['local']);
					} else {
						$obj = ApiRecord::getInstance($many['class']);
						$rhs = 'SELECT '.$obj->getSoftKeyDefinition().' FROM '.$obj->getTableName().' __tbl'.
							' WHERE __tbl.'.$many['foreign'].' = '.new QueryProperty(($prefix ? $prefix : 't1').'.'.$many['local']);
					}
					if ($rhs)
						return '('.$params[0].' IN ('.$rhs.'))';
				}
			}
			require_once('DataStore.class.php');
			DataStore::set('query_exception', new ParseException('Illegal right-hand side for IN'));
			return 'FALSE';
		} else
			return '('.$params[0].' IN ('.implode(', ', $params[1]).'))';
	}
	
	protected function le($params) {
		return '('.$params[0].' <= '.$params[1].')';
	}
	
	protected function lt($params) {
		return '('.$params[0].' < '.$params[1].')';
	}
	
	protected function match($params) {
		if (!$this->matchAlias)
			throw new ParseException('Match only allowed in where clause');
		if (array_key_exists('weight', $params))
			if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $params['weight']))
				throw new ParseExpression('Float expected and not found in match FROM value');
			else
				$weight = (float) $params['weight'];
		else
			$weight = 0.75;
		return 'coalesce("'.$this->matchAlias.'".value, 0) >= '.$weight;
	}
	
	protected function mod($params) {
		return '('.implode(' % ', $params).')';
	}
	
	protected function mul($params) {
		return '('.implode(' * ', $params).')';
	}
	
	protected function ne($params) {
		return '('.$params[0].' != '.$params[1].')';
	}
	
	protected function neg($params) {
		return '(-'.$params[0].')';
	}
	
	protected function or_($params) {
		return '('.implode(' OR ', $params).')';
	}
	
	protected function sub($params) {
		return '('.implode(' - ', $params).')';
	}
	
	public function __toString() {
	try{
		if (method_exists($this, $fn = $this->function) || method_exists($this, $fn = $this->function.'_'))
			return $this->$fn($this->parameters);
		else
			return $this->function.'('.implode(', ', array_map(create_function('$k,$v',
				'return (is_int($k) ? "" : $k."=").(is_array($v) ? "(".implode(", ", $v).")" : $v);'),
				array_keys($this->parameters), $this->parameters)).')';
	} catch (Exception $e) { var_dump($e);die;}
	}

	/* Recursively call a method on the function parameters */
	protected function recursiveCall($call, $initial = array(), $reduceFn = 'array_merge', $finalFn = 'array_unique' /*, ...*/) {
		$result = $initial;
		$params = array_slice(func_get_args(), 4);
		foreach ($this->parameters as $par)
			/* Handle array parameters (e.g. for the in function) seperately */
			if (is_array($par))
				foreach ($par as $val) {
					$fnResult = call_user_func_array(array($val, $call), $params);
					if ($reduceFn)
						$result = call_user_func($reduceFn, $result, $fnResult);
				}
			/* Handle objects */
			elseif (is_object($par)) {
				$fnResult = call_user_func_array(array($par, $call), $params);
				if ($reduceFn)
					$result = call_user_func($reduceFn, $result, $fnResult);
			}
		return $finalFn ? call_user_func($finalFn, $result) : $result;
	}

	public function propertyList() {
		return $this->recursiveCall('propertyList');
	}
	
	protected $aggregateFunctions = array('count', 'sum', 'min', 'max', 'avg');
	
	public function aggregatePropertyList() {
		if (in_array($this->function, $this->aggregateFunctions))
			/* If this function is an aggregate, return all properties refered to within this function */
			return $this->recursiveCall('propertyList');
		else
			/* Otherwise, delegate the call to the parameters */
			return $this->recursiveCall('aggregatePropertyList');
	}
	
	public function nonAggregatePropertyList() {
		if (in_array($this->function, $this->aggregateFunctions))
			/* If this function is an aggregate, return an empty list */
			return array();
		else
			/* Otherwise, delegate the call to the parameters */
			return $this->recursiveCall('nonAggregatePropertyList');
	}
	
	public function tableReferenceList() {
		$result = $this->recursiveCall('tableReferenceList');
/*		if ($this->function == 'in' && count($this->parameters[1]) == 1 && $this->parameters[1][0] instanceof QueryProperty) {
			$prop = new QueryProperty($this->parameters[1][0]->property.'.id');
			$result = array_unique(array_merge($result, $prop->tableReferenceList()));
		}*/
		return $result;
	}
	
	protected function containingTableFinalFn($result) {
		$result = array_unique(array_filter($result));
		if (count($result) > 1)
			throw new ParseException('Multiple tables referred in single select clause');
			//TODO: accept parent tables
		return $result ? reset($result) : null;
	}
	
	public function containingTable($inAggregate = false) {
		return $this->recursiveCall(
			'containingTable', array(), create_function('$x,$y', '$x[] = $y; return $x;'), array($this, 'containingTableFinalFn'),
			$inAggregate || in_array($this->function, $this->aggregateFunctions)
		);
	}

	public function renderWithLiterals($literals = array()) {
		$this->recursiveCall('renderWithLiterals', null, null, null, $literals);
	}
	
	public function renderWithQueries($tables = array(), $joins = array()) {
		$this->tables = $tables;
		$this->joins = $joins;
		$this->recursiveCall('renderWithQueries', null, null, null, $tables);
	}
	
	public function joinClauses($from, $joins) {
		if ($this->function == 'match') {
			$outTable = ApiRecord::getInstance($from)->getTableName();
			$inTable = ApiRecord::getInstance($this->parameters[0])->getTableName();
			$result = array($this->matchAlias => array(
				array(DBs::inst(DBs::SYSTEM)->formatQuery('match_tags(%, %, %l)', $outTable, $inTable, $this->parameters[1])),
				't1.id = "'.$this->matchAlias.'".id',
				'(id bigint, value double precision)'
			));
		} else
			$result = array();
		return $this->recursiveCall('joinClauses', $result, 'array_merge', null, $from, $joins);
	}

	public function orderClauses($orders) {
		if ($this->function == 'match')
			$result = array(new QueryOrder(
				new QueryFunction('coalesce', array(new QueryProperty($this->matchAlias.'.value'), new QueryValue(0))),
				'desc'
			));
		else
			$result = array();
		return $this->recursiveCall('orderClauses', $result, 'array_merge', null, $orders);
	}
}

/* Representation of a single order clause */
class QueryOrder implements QueryExpression {
	public $expression;
	public $direction;
	
	public function __construct($expression, $direction) {
		$this->expression = $expression;
		$this->direction = $direction;
	}
	
	public function __toString() {
		return (string) $this->expression.' '.$this->direction;
	}
	
	public function propertyList() {
		return $this->expression->propertyList();
	}
	
	public function aggregatePropertyList() {
		return $this->expression->aggregatePropertyList();
	}
	
	public function nonAggregatePropertyList() {
		return $this->expression->nonAggregatePropertyList();
	}
	
	public function tableReferenceList() {
		return $this->expression->tableReferenceList();
	}
	
	public function containingTable($inAggregate = false) {
		return $this->expression->containingTable($inAggregate);
	}
	
	public function renderWithLiterals($literals = array()) {
		$this->expression->renderWithLiterals($literals);
	}

	public function renderWithQueries($tables = array(), $joins = array()) {
		$this->expression->renderWithQueries($tables);
	}
	
	public function joinClauses($from, $joins) {
		return $this->expression->joinClauses($from, $joins);
	}

	public function orderClauses($orders) {
		return $this->expression->orderClauses($orders);
	}
}

?>
