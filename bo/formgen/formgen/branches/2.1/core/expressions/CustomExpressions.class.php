<?php

require_once('Expression.class.php');
require_once('BaseExpressions.class.php');

// File: Expression classes
// Contains classes for language-independent representation of expressions.

/*
	Class: GenericExpr
	A generic expression container.
	This node can be used for expressions that are fairly specific and it's therefore not
	really convenient to write a new expression class for them.
*/
class GenericExpr extends Expression {
	private $php, $js, $eval, $getTargets, $data;

	/*
		Constructor: __construct

		Parameters:
		$phpFn  - A callback to be called when a PHP representation is needed.
		$jsFn   - A callback to be called when a Javascript representation is needed.

		The respective callbacks should accept the same parameters as <Expression::getPHP>,
		<Expression::getJS>, and return the string representation.
	*/
	public function __construct($phpFn, $jsFn, $eval, $getTargets, $data = null) {
		$this->php  = $phpFn;
		$this->js   = $jsFn;
		$this->eval = $eval;
		$this->getTargets = $getTargets;
		$this->data = $data;
	}

	public function getPHP($indent) { return call_user_func($this->php, $indent, $this->data); }
	public function getJS($indent)  { return call_user_func($this->js, $indent, $this->data); }
	public function evaluate()      { return call_user_func($this->eval, $this->data); }

	public function getTargets()    { return call_user_func($this->getTargets, $this->data); }
}

class InputExpr extends Expression {
	protected $input;

	public function __construct(InputValue $input) {
		$this->input = $input;
	}

	public function getPHP($indent) {
		return '$data[\''.$this->input->getName().'\']';
	}

	public function getJS($indent) {
		return $this->input->getJSValue();
	}

	public function getInput() {
		return $this->input;
	}

	public function evaluate() {
		return $this->input->getValue();
	}

	public function getTargets() {
		return array($this->input->getInputElement());
	}
}

/*
	Class: IsGivenExpr
	An expression to check whether an expression is given.
	Generally the child of this will be a <FormElExpr>.
*/
class IsGivenExpr extends UnaryExpr {
	/*
		Constructor: __construct

		Parameters:
		$child - The expression to check.
	*/
	public function __construct(InputExpr $child) {
		parent::__construct('', $child);
	}

	public function getPHP($indent) {
		$php = $this->child->getPHP($indent);
		return '(isset('.$php.') && '.$php.'!=\'\')';
	}

	public function getJS($indent) {
		$php = $this->child->getJS($indent);
		return $php.'!=\'\'';
	}

	public function evaluate() {
		return $this->child->getInput()->isGiven();
	}
}

/*
	Class: ValidEnumExpr
	Checks whether an expression is within a specific set of values.

	Note:
	Currently always returns *true* in javascript, since this expression tends
	to be used in dropdown, radio button, or similar controls. These by definition
	fall into the valid values.
*/
class ValidEnumExpr extends UnaryExpr {
	protected $values;

	/*
		Constructor: __construct

		Parameters:
		$child  - The expression to check.
		$values - An array of values the expression may resolve to.
	*/
	public function __construct(Expression $child, array $values) {
		parent::__construct('', $child);
		$this->values = $values;
	}

	public function getPHP($indent) {
		return 'in_array('.$this->child->getPHP($indent).', array('.implode(',', array_map('makeString', $this->values)).'))';
	}

	public function getJS($indent) {
		return 'true';
	}

	public function evaluate() {
		return in_array($this->child->evaluate(), $this->values);
	}
}

?>