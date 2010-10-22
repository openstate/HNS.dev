<?php

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
	abstract public function getJS($indent);

	abstract public function evaluate();

	abstract public function getTargets();
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

	public function getTargets() {
		$result = array();
		foreach ($this->getChildren() as $c)
			$result = array_merge($result, $c->getTargets());
		return $result;
	}
}

?>