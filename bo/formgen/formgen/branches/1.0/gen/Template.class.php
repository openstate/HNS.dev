<?php

require_once('HTML.class.php');

/*
	Class: HTMLTemplateNode
	A node that is a placeholder in a <Form template>.
	This tag will replace itself with other HTML nodes when <HTMLNode::templateClone> is used.
	Otherwise, it behaves as a <HTMLText> node.
*/
class HTMLTemplateNode extends HTMLText {
	protected $id;

	/*
		Constructor: __construct

		Parameters:
		$id - The name of this node, this is used in the replacement to determine what nodes
		      come in place of this node.
	*/
	public function __construct($id) { $this->id = $id; }
	public function getID() { return $this->id; }

	/*
		Method: templateClone
		Replaces this node with other nodes.
		The parameter array should contain an entry for this node's id. These nodes
		are then returned instead of this node.
	*/
	public function templateClone($params) {
		if (!isset($params[$this->id]))
			throw new ParseException('Data for template tag '.$this->id.' not set');
		return $params[$this->id];
	}
}

/*
	Class: ErrorPlaceholder
	A node that represents a position where an error message may be placed.
	The node itself does nothing special.
*/
class ErrorPlaceholder extends HTMLText {
	public function __construct() {}
}

?>
