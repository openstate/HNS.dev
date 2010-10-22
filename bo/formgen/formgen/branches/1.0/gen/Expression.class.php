<?php

// File: Expression classes
// Contains classes for language-independent representation of expressions.

function makeString($s) {
	return '\''.addslashes($s).'\'';
}

/*
	Class: Expression
	Base class for all expression nodes.
*/
abstract class Expression {
//	protected $parent = null;

	/*
		Method: getPHP
		Returns the expression represented in PHP.

		Parameters:
		$indent - The current indentation string that should be concatenated after newlines.

		Returns:
		A string representing the expression in PHP.
	*/
	abstract public function getPHP($indent);

	/*
		Method: getJS
		Returns the expression represented in JavaScript.

		Parameters:
		$indent - The current indentation string that should be concatenated after newlines.
		$parser - An instance of <DescParser>. In Javascript, some form elements are accessed
		          in different ways, so the expression then needs to know its input type.
							With a DescParser it can ask this information.

		Returns:
		A string representing the expression in JavaScript.
	*/
	abstract public function getJS($indent, DescParser $parser);

	/*
		Method: getHTML
		Returns the expression for use in HTML templates.
		Currently this uses Smarty syntax.

		Returns:
		A string representing the expression for use in HTML templates.
	*/
	abstract public function getHTML();
}

/*
	Class: GenericExpr
	A generic expression container.
	This node can be used for expressions that are fairly specific and it's therefore not
	really convenient to write a new expression class for them.
*/
class GenericExpr extends Expression {
	private $php, $js, $html;

	/*
		Constructor: __construct

		Parameters:
		$phpFn  - A callback to be called when a PHP representation is needed.
		$jsFn   - A callback to be called when a Javascript representation is needed.
		$htmlFn - A callback to be called when a HTML template representation is needed.

		The respective callbacks should accept the same parameters as <Expression::getPHP>,
		<Expression::getJS> and <Expression::getHTML>, and return the string representation.
	*/
	public function __construct($phpFn, $jsFn, $htmlFn) {
		$this->php = $phpFn;
		$this->js = $jsFn;
		$this->html = $htmlFn;
	}

	public function getPHP($indent) { return call_user_func($this->php, $indent); }
	public function getJS($indent, DescParser $parser)  { return call_user_func($this->js, $indent, $parser); }
	public function getHTML()       { return call_user_func($this->html); }
}

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

	public function getJS($indent, DescParser $parser) {
		if (is_null($this->value))
			return 'null';
		else if (is_string($this->value))
			return makeString($this->value);
		else
			return (string)$this->value;
	}

	public function getHTML() {
		if (is_null($this->value))
			return '\'\'';
		else if (is_string($this->value))
			return makeString($this->value);
		else
			return (string)$this->value;
	}
}

/*
	Class: ChildExpr
	A base expression class for expressions that can have child nodes.
*/
abstract class ChildExpr extends Expression {
	/*
		Method: getChildren
		Returns the children of this expression node.

		Returns:
		An array of <Expression> nodes.
	*/
	abstract public function getChildren();
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

	public function getJS($indent, DescParser $parser) {
		return $this->op.$this->child->getJS($indent, $parser);
	}

	public function getHTML() {
		return $this->op.$this->child->getHTML();
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

	public function getJS($indent, DescParser $parser) {
		return '('.$this->left->getJS($indent.' ', $parser).' '.$this->op.' '.$this->right->getJS($indent.' ', $parser).')';
	}

	public function getHTML() {
		return '('.$this->left->getHTML().' '.$this->op.' '.$this->right->getHTML().')';
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

	public function getJS($indent, DescParser $parser) {
		if ($this->guard instanceof TernaryExpr)
			$val = '('.$this->guard->getJS($indent, $parser).')';
		else
			$val = $this->guard->getJS($indent, $parser);
		$val.= ' ? ';

		if ($this->iftrue instanceof TernaryExpr)
			$val.= '('.$this->iftrue->getJS($indent, $parser).')';
		else
			$val.= $this->iftrue->getJS($indent, $parser);
		$val.= ' : ';

		if ($this->iffalse instanceof TernaryExpr)
			$val.= '('.$this->iffalse->getJS($indent, $parser).')';
		else
			$val.= $this->iffalse->getJS($indent, $parser);

		return $val;
	}

	public function getHTML() {
		if ($this->guard instanceof TernaryExpr)
			$val = '('.$this->guard->getHTML().')';
		else
			$val = $this->guard->getHTML();
		$val.= ' ? ';

		if ($this->iftrue instanceof TernaryExpr)
			$val.= '('.$this->iftrue->getHTML().')';
		else
			$val.= $this->iftrue->getHTML();
		$val.= ' : ';

		if ($this->iffalse instanceof TernaryExpr)
			$val.= '('.$this->iffalse->getHTML().')';
		else
			$val.= $this->iffalse->getHTML();

		return $val;
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

	public function getChildren() { return array($this->exprs); }

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

	public function getJS($indent, DescParser $parser) {
		if (count($this->exprs)==0)
			return '';
		if (count($this->exprs)==1)
			return reset($this->exprs)->getJS($indent.' ', $parser);

		$php = array();
		foreach ($this->exprs as $ex)
			$php[]= $ex->getJS($indent.' ', $parser);
		return '('.implode(' '.$this->op.' ', $php)."\n)";
	}

	public function getHTML() {
		if (count($this->exprs)==0)
			return '';
		if (count($this->exprs)==1)
			return reset($this->exprs)->getHTML();

		$php = array();
		foreach ($this->exprs as $ex)
			$php[]= $ex->getHTML();
		return '('.implode(' '.$this->op.' ', $php).')';
	}
}

/*
	Class: FormElExpr
	Represents a form element.
	This expression will translate into a piece of code that requests the value of the
	given form element.
*/
class FormElExpr extends Expression {
	protected $input;
	protected $formDataVar;

	/*
		Constructor: __construct

		Parameters:
		$name - The name of the element.
	*/
	public function __construct(HTMLInput $input) {
		$this->input = $input;
		$this->formDataVar = $GLOBALS['formDataVar'];
	}

	public function getName() { return $this->input->getName(); }

	public function getPHP($indent) {
		return $this->input->getPHPvalue();
	}

	public function getJS($indent, DescParser $parser) {
		return $this->input->getJSvalue();
	}

	public function getHTML() {
		return $this->input->getHTMLvalue($this->formDataVar);
	}
}


class FormPostExpr extends Expression {
	protected $name;
	protected $formDataVar;
	protected $index;

	/*
		Constructor: __construct

		Parameters:
		$name - The name of the element.
	*/
	public function __construct($name, $index = null) {
		$this->name = $name;
		$this->formDataVar = $GLOBALS['formDataVar'];
		$this->index = $index;
	}

	public function getName() { return $this->name; }

	public function getPHP($indent) {
		if ($this->index === null)
			$index = '';
		else
			$index = '['.$this->index.']';
		return '$post[\''.$this->name.'\']'.$index;
	}

	public function getJS($indent, DescParser $parser) { return '';	}
	public function getHTML() { return ''; }
}

class FormFilesExpr extends FormPostExpr {
	public function getPHP($indent) {
		if ($this->index === null)
			$index = '';
		else
			$index = '['.$this->index.']';
		return '$_FILES[\''.$this->name.'\']'.$index;
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
	public function __construct(Expression $child) {
		parent::__construct('', $child);
	}

	public function getPHP($indent) {
		$php = $this->child->getPHP($indent);
		return '(isset('.$php.') && '.$php.'!=\'\')';
	}

	public function getJS($indent, DescParser $parser) {
		$php = $this->child->getJS($indent, $parser);
		return $php.'!=\'\'';
	}

	public function getHTML() {
		$php = $this->child->getHTML();
		return '(isset('.$php.') && '.$php.')';
	}
}

/*
	Class: IsGivenExpr
	An expression to check whether an expression is set.
	Generally the child of this will be a <FormElExpr>.

	Note:
	Currently is undefined for JavaScript.
*/
class IssetExpr extends UnaryExpr {
	/*
		Constructor: __construct

		Parameters:
		$child - The expression to check.
	*/
	public function __construct(Expression $child) {
		parent::__construct('', $child);
	}

	public function getPHP($indent) {
		return 'isset('.$this->child->getPHP($indent).')';
	}

	public function getJS($indent, DescParser $parser) {
		return '(?IssetExpr?)';
	}

	public function getHTML() {
		return 'isset('.$this->child->getHTML().')';
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

	public function getJS($indent, DescParser $parser) {
		return 'true';
	}

	public function getHTML() {
		return 'in_array('.$this->child->getPHP().', array('.implode(',', array_map('makeString', $this->values)).'))';
	}
}

?>