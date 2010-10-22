<?php

/*
	Interface for query syntax tree elements
*/
interface QueryExpression {
	/* Alias table names in the expression */
	public function alias($aliases);

	/* List of properties used in the expression */
	public function propertyList();
	
	/* List of properties used in aggregates in the expression */
	public function aggregatePropertyList();
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
		return '"'.$this->property.'"';
	}
	
	public function alias($aliases) {
		$prefix = implode('.', array_slice(explode('.', $aliases), 0, -1));
		return @$aliases[$prefix] ? $aliases[$prefix].substr((string) $this->value, strlen($prefix)) : (string) $this->value;
	}
	
	public function propertyList() {
		return array($this->property);
	}
	
	public function aggregatePropertyList() {
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
	
	public function alias($aliases) {
		return (string) $this->value;
	}
	
	public function propertyList() {
		return array();
	}
	
	public function aggregatePropertyList() {
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
	
	public function __toString() {
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

	public function alias() {
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

}

?>
