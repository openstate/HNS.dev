<?php

require_once('Expression.class.php');

// File: Expression classes
// Contains classes for language-independent representation of expressions.

/*
	Class: ValueExpr
	An expression that represents values.
	Currently supports integers, floats, strings and nulls.
*/
class ValueExpr extends Expression {
	protected $value;

	/*
		Constructor: __construct

		Parameters:
		$value - The value this expression contains.
	*/
	public function __construct($value) { $this->value = $value; }

	public function getPHP($indent) {
		if (is_null($this->value))
			return 'null';
		else if (is_string($this->value))
			return makeString($this->value);
		else
			return (string)$this->value;
	}

	public function getJS($indent) {
		if (is_null($this->value))
			return 'null';
		else if (is_string($this->value))
			return makeString($this->value);
		else
			return (string)$this->value;
	}

	public function evaluate() { return $this->value; }

	public function getTargets() { return array(); }
}

/*
	Class: UnaryExpr
	A class for unary operation expressions.
*/
class UnaryExpr extends ChildExpr {
	protected $child;
	protected $op;

	/*
		Constructor: __construct

		Parameters:
		$op    - A string representing the operator.
		$child - The expression the operator applies to.
	*/
	public function __construct($op, Expression $child) {
		$this->child = $child;
		$this->op = $op;
	}

	public function getChildren() { return array($this->child); }

	public function getPHP($indent) {
		return $this->op.$this->child->getPHP($indent);
	}

	public function getJS($indent) {
		return $this->op.$this->child->getJS($indent);
	}

	public function evaluate() {
		return eval('return '.$this->op.' '.var_export($this->child->evaluate(), true).';');
	}
}

/*
	Class: BinaryExpr
	A class for binary operation expressions.
*/
class BinaryExpr extends ChildExpr {
	protected $left, $right;
	protected $op;

	/*
		Constructor: __construct

		Parameters:
		$op    - A string representing the operator.
		$left  - The left-hand parameter of the operator.
		$right - The right-hand parameter of the operator.
	*/
	public function __construct($op, Expression $left, Expression $right) {
		$this->left = $left;
		$this->right = $right;
		$this->op = $op;
	}

	public function getChildren() { return array($this->left, $this->right); }

	public function getPHP($indent) {
		return '('.$this->left->getPHP($indent.' ').' '.$this->op.' '.$this->right->getPHP($indent.' ').')';
	}

	public function getJS($indent) {
		return '('.$this->left->getJS($indent.' ').' '.$this->op.' '.$this->right->getJS($indent.' ').')';
	}

	public function evaluate() {
		return eval('return '.
			var_export($this->left->evaluate(), true).
			' '.$this->op.' '.
			var_export($this->right->evaluate(), true).';'
		);
	}
}

/*
	Class: BinaryExpr
	A class for representing the ternary operator (?:).
*/
class TernaryExpr extends ChildExpr {
	protected $guard, $iftrue, $iffalse;

	/*
		Constructor: __construct

		Parameters:
		$guard   - The expression that represents the test.
		$iftrue  - The expression to return if the guard is true.
		$iffalse - The expression to return if the guard is false.
	*/
	public function __construct(Expression $guard, Expression $iftrue, Expression $iffalse) {
		$this->guard =   $guard;
		$this->iftrue =  $iftrue;
		$this->iffalse = $iffalse;
	}

	public function getChildren() { return array($this->guard, $this->iftrue, $this->iffalse); }

	public function getPHP($indent) {
		if ($this->guard instanceof TernaryExpr)
			$val = '('.$this->guard->getPHP($indent).')';
		else
			$val = $this->guard->getPHP($indent);
		$val.= ' ? ';

		if ($this->iftrue instanceof TernaryExpr)
			$val.= '('.$this->iftrue->getPHP($indent).')';
		else
			$val.= $this->iftrue->getPHP($indent);
		$val.= ' : ';

		if ($this->iffalse instanceof TernaryExpr)
			$val.= '('.$this->iffalse->getPHP($indent).')';
		else
			$val.= $this->iffalse->getPHP($indent);

		return '('.$val.')';
	}

	public function getJS($indent) {
		if ($this->guard instanceof TernaryExpr)
			$val = '('.$this->guard->getJS($indent).')';
		else
			$val = $this->guard->getJS($indent);
		$val.= ' ? ';

		if ($this->iftrue instanceof TernaryExpr)
			$val.= '('.$this->iftrue->getJS($indent).')';
		else
			$val.= $this->iftrue->getJS($indent);
		$val.= ' : ';

		if ($this->iffalse instanceof TernaryExpr)
			$val.= '('.$this->iffalse->getJS($indent).')';
		else
			$val.= $this->iffalse->getJS($indent);

		return $val;
	}

	public function evaluate() {
		return
			$this->guard->evaluate() ?
			$this->iftrue->evaluate() :
			$this->iffalse->evaluate()
		;
	}
}

/*
	Class: NaryExpr
	A class for representing expressions with many children to which the same operator is applied.
	If any given children are null, they will be left out of the final representation.
*/
class NaryExpr extends ChildExpr {
	protected $exprs;
	protected $op;

	/*
		Constructor: __construct

		Parameters:
		$op    - A string representing the operator.
		$exprs - An array of expressions that the operator applies to.
	*/
	public function __construct($op, array $exprs) {
		$this->exprs = array_filter($exprs);
		$this->op = $op;
	}

	public function getChildren() { return $this->exprs; }

	/*
		Method: add
		Adds a single expression to the end of the expression list.

		Parameters:
		$ex - The expression to add
	*/
	public function add(Expression $ex) { if ($ex) $this->exprs[]= $ex; }
	/*
		Method: addFirst
		Adds a single expression to the beginning of the expression list.

		Parameters:
		$ex - The expression to add
	*/
	public function addFirst(Expression $ex) { if ($ex) array_unshift($this->exprs, $ex); }

	public function getPHP($indent) {
		if (count($this->exprs)==0)
			return '';
		if (count($this->exprs)==1)
			return reset($this->exprs)->getPHP($indent.' ');

		$php = array();
		foreach ($this->exprs as $ex)
			$php[]= $ex->getPHP($indent.' ');
		return '('.implode(' '.$this->op.' ', $php)."\n)";
	}

	public function getJS($indent) {
		if (count($this->exprs)==0)
			return '';
		if (count($this->exprs)==1)
			return reset($this->exprs)->getJS($indent.' ');

		$php = array();
		foreach ($this->exprs as $ex)
			$php[]= $ex->getJS($indent.' ');
		return '('.implode(' '.$this->op.' ', $php)."\n)";
	}

	public function evaluate() {
		$vals = array();
		foreach ($this->exprs as $ex)
			$vals[] = var_export($ex->evaluate(), true);

		return eval('return '.implode(' '.$this->op.' ', $vals).';');
	}
}

?>