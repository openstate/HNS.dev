<?php

// File: Statement classes
// Contains classes for language-independent representation of statements.

require_once('Expression.class.php');

/*
	Class: Statement
	Base class for all statement nodes.
*/
abstract class Statement {
	/*
		Method: getPHP
		Returns the statement represented in PHP.

		Parameters:
		$indent - The current indentation string that should be concatenated after newlines.

		Returns:
		A string representing the statement in PHP.
	*/
	abstract public function getPHP($indent);

	/*
		Method: getJS
		Returns the expression represented in JavaScript.

		Parameters:
		$indent - The current indentation string that should be concatenated after newlines.
		$parser - An instance of <DescParser>. Generally needed because <Expression::getJS> needs
		          this parameter.

		Returns:
		A string representing the statement in JavaScript.
	*/
	abstract public function getJS($indent, DescParser $parser);
}

/*
	Class: GenericStatement
	A generic statement container.
	This node can be used for statements that are fairly specific and it's therefore not
	really convenient to write a new statement class for them.
*/
class GenericStatement extends Statement {
	protected $php, $js;

	/*
		Constructor: __construct

		Parameters:
		$php - A string representing the statement in PHP.
		$js  - A string representing the statement in JavaScript.
	*/
	public function __construct($php, $js) {
		$this->php = $php;
		$this->js = $js;
	}

	public function getPHP($indent) {
		return $this->php;
	}

	public function getJS($indent, DescParser $parser) {
		return $this->js;
	}
}

/*
	Class: AssignStatement
	Representation of an assignment.
*/
class AssignStatement extends Statement {
	protected $left, $right;

	/*
		Constructor: __construct

		Parameters:
		$left  - The expression to assign to.
		$right - The expression to assign to the left expression.
	*/
	public function __construct(Expression $left, Expression $right) {
		$this->left = $left;
		$this->right = $right;
	}

	public function getPHP($indent) {
		return $this->left->getPHP($indent).' = '.$this->right->getPHP($indent).';';
	}

	public function getJS($indent, DescParser $parser) {
		return $this->left->getJS($indent, $parser).' = '.$this->right->getJS($indent, $parser).';';
	}
}

/*
	Class: IfStatement
	Representation of an if-statement.
*/
class IfStatement extends Statement {
	protected $guard, $then, $else;

	/*
		Constructor: __construct

		Parameters:
		$guard - The expression to check for truth
		$then  - The statement to execute when $guard evaluates to true.
		$else  - The statement to execute when $guard evaluates to false.
	*/
	public function __construct(Expression $guard, Statement $then, $else = null) {
		$this->guard = $guard;
		$this->then = $then;
		$this->else = $else;
	}

	/*
		Method: setElse
		Changes the statement used for the *else* part.

		Parameters:
		$else - The new statement to execute when the guard evaluates to false.
	*/
	public function setElse(Statement $else) {
		$this->else = $else;
	}

	public function getPHP($indent) {
		$php = 'if ('.$this->guard->getPHP($indent.'    ').') '.$this->then->getPHP($indent);
		if ($this->else)
			$php.= "\n".$indent.'else '.$this->else->getPHP($indent);
		return $php;
	}

	public function getJS($indent, DescParser $parser) {
		$php = 'if ('.$this->guard->getJS($indent.'    ', $parser).') '.$this->then->getJS($indent, $parser);
		if ($this->else)
			$php.= "\n".$indent.'else '.$this->else->getJS($indent, $parser);
		return $php;
	}
}

/*
	Class: GroupStatement
	Representation of a list of statements.
*/
class GroupStatement extends Statement {
	protected $stmts;

	/*
		Constructor: __construct

		Parameters:
		$stmts - An array of <Statements> that form the group.
	*/
	public function __construct(array $stmts) {
		$this->stmts = $stmts;
	}

	/*
		Method: addStatement
		Adds a statement to the group.

		Parameters:
		$stmt - The statement to add.
	*/
	public function addStatement(Statement $stmt) {
		$this->stmts[]= $stmt;
	}

	public function getPHP($indent) {
		if (count($this->stmts)==1)
			return reset($this->stmts)->getPHP($indent);
		else {
			$str = "{\n";
			$newIndent = $indent."\t";
			foreach ($this->stmts as $stmt)
				$str.= $newIndent.$stmt->getPHP($newIndent)."\n";
			$str.= $indent."}\n";
		}
		return $str;
	}

	public function getJS($indent, DescParser $parser) {
		if (count($this->stmts)==1)
			return reset($this->stmts)->getJS($indent, $parser);
		else {
			$str = "{\n";
			$newIndent = $indent."\t";
			foreach ($this->stmts as $stmt)
				$str.= $newIndent.$stmt->getJS($newIndent, $parser)."\n";
			$str.= $indent."}\n";
		}
		return $str;
	}
}

?>