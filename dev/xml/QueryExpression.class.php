<?php

/*
	Interface for query syntax tree elements
*/
interface QueryExpression {
	/* List of properties used in the expression */
	public function propertyList();
	
	/* List of properties used in aggregates in the expression */
	public function aggregatePropertyList();

	/* List of tables used in properties used in the expression */
	public function tableReferenceList();
}

/*
	Representation of a property in a query
*/
class QueryProperty implements QueryExpression {
	public $property;
	
	public function __construct($property) {
		$this->property = $property;
	}
	
	public function __toString() {
		$parts = explode('.', $this->property);
		$last = array_pop($parts);
		return '"'.implode('__', $parts).($parts ? '' : 't1').'"."'.$last.'"';
	}
	
	public function propertyList() {
		return array($this);
	}
	
	public function aggregatePropertyList() {
		return array();
	}
	
	public function tableReferenceList() {
		$parts = explode('.', $this->property);
		array_pop($parts);
		return $parts ? array(implode('::', $parts)) : array();
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
	
	public function tableReferenceList() {
		return array();
	}
}

/*
	Representation of a function in a query
*/
class QueryFunction implements QueryExpression {
	public $function;
	public $parameters;
	
	public function __construct($function, $params) {
		$this->function = $function;
		$this->parameters = $params;
	}

	protected function eq($params) {
		return '('.$params[0].' = '.$params[1].')';
	}

	protected function ne($params) {
		return '('.$params[0].' != '.$params[1].')';
	}
	
	public function __toString() {
		if (method_exists($this, $fn = $this->function) || method_exists($this, $fn = $this->function.'_'))
			return $this->$fn($this->parameters);
		else
			return $this->function.'('.implode(', ', array_map(create_function('$k,$v',
				'return (is_int($k) ? "" : $k."=").(is_array($v) ? "(".implode(", ", $v).")" : $v);'),
				array_keys($this->parameters), $this->parameters)).')';
	}

	/* Recursively call a method on the function parameters */
	protected function recursiveCall($call) {
		$result = array();
		foreach ($this->parameters as $par)
			/* Handle array parameters (e.g. for the in function) seperately */
			if (is_array($par))
				foreach ($par as $val)
					$result = array_merge($result, $val->$call());
			/* Handle objects */
			elseif (is_object($par))
				$result = array_merge($result, $par->$call());
		return array_unique($result);
	}

	public function propertyList() {
		return $this->recursiveCall('propertyList');
	}
	
	public function aggregatePropertyList() {
		if (in_array($this->function, array('count', 'sum', 'min', 'max', 'avg')))
			/* If this function is an aggregate, return all properties refered to within this function */
			return $this->recursiveCall('propertyList');
		else
			/* Otherwise, delegate the call to the parameters */
			return $this->recursiveCall('aggregatePropertyList');
	}
	
	public function tableReferenceList() {
		return $this->recursiveCall('tableReferenceList');
	}

}

?>
