<?php

/*
	File: HTML classes
	Contains a number of classes to represent basic HTML nodes.
*/

require_once('Check.class.php');
require_once('Expression.class.php');
require_once('Statement.class.php');

/*
	Class: HTMLNode
	Base class for all HTML nodes.
*/
abstract class HTMLNode {
	/*
		Method: getHTML
		Returns a string representation of the HTML of this node.
	*/
	abstract public function getHTML();

	/*
		Method: getConditionalStmt
		Collects conditional statements.
		Due to checks and conditions on nodes, certain statements related to <HTMLInputs> (such
		as validation and extracting form variables) should only be executed under certain
		conditions. This function recurses through nodes and collects the statements and conditions.

		Parameters:
		$leafCallback - A callback function executed when a <HTMLInput> is encountered. This function
		                should take a single HTMLInput parameter, and return the same as this function.
		$addRequired  - Indicates that required checks should be added in the result.

		Returns:
		An array of associative arrays,
		with the keys:
		cond - The condition when the associated statements should be executed
		stmt - A <Statement> to execute.
	*/
	abstract public function getConditionalStmt($leafCallback, $addRequired = true);

	/*
		Method: getConversions
		Collects value conversion statements.

		Normally, it is assumed that the actual value to be handled is equal to the value
		submitted in the form. Some input types may have this different though, e.g.
		check inputs will actually use a boolean value equal to whether the input was set
		or not. This function collects statements to be executed to convert these values.

		Returns:
		An array of <Statements> to convert values.
	*/
	abstract public function getConversions();

	/*
		Method: getDefaults
		Collects default input values.

		Returns:
		An array with the keys being input names and the values the default value for that
		input.
	*/
	abstract public function getDefaults();

	/*
		Method: plainClone
		Clones this node and its children.
		Performs a deep copy.

		Note:
		It is a bit odd to have this function - using the clone keyword and implementing
		__clone should work as well. But due to some issues with templateClone and DescParser,
		this function is currently used. This should be looked into.
	*/
	public function plainClone() {
		return clone $this;
	}

	/*
		Method: templateClone
		Clones this node and its children, replacing template placeholders.
		Performs a deep copy.

		Parameters:
		$params - An array with keys being the names of placeholder tags, and the values being
		          HTMLNodes to replace them with.
	*/
	public function templateClone($params) {
		return clone $this;
	}

	public function getExtraJS($js = '') {
		return $js;
	}
}

/*
	Class: HTMLText
	Holds plain text.
	HTML entities are automatically generated for special characters.
*/
class HTMLText extends HTMLNode {
	protected $text = '';

	/*
		Constructor: __construct

		Parameters:
		$text - The text contained in this node. Should not contain HTML entities.
	*/
	public function __construct($text) {
		$this->text = $text;
	}

	public function getHTML() {
		$parts = preg_split('/({.*?})/s', $this->text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$text = '';
		$inSmarty = false;
		foreach ($parts as $part) {
			if ($inSmarty)
				$text.= $part;
			else
				$text.= htmlentities($part, ENT_COMPAT, 'UTF-8');
			$inSmarty = !$inSmarty;
		}
		return $text;
	}

	public function getConditionalStmt($leafCallback, $addRequired = true) { return array(); }
	public function getConversions() { return array(); }
	public function getDefaults()    { return array(); }
}

/*
  Class: HTMLTag
	General representation of HTML nodes.
	This class is generally sufficient for holding HTML nodes.
*/
class HTMLTag extends HTMLNode {
	// Group: Condition constants

	// Constant: Hide
	// When this node's condition is not met, it should be visibly hidden.
	const Hide    = 0;
	// Constant: Disable
	// When this node's condition is not met, contained input controls should be disabled.
	const Disable = 1;

	// Group: Properties
	// Property: $type
	// The type of tag, e.g. 'table' or 'em'. May also be the empty string.
	protected $type = '';
	// Property: $attributes
	// An associative array of attribute names and their values.
	protected $attributes = array();
	// Property: $children
	// An array of all this node's children.
	protected $children = array();
	// Property: $disableMethod
	// Determines what to do when this node's condition is not met. Should be set to <Hide> or <Disable>.
	public    $disableMethod = self::Hide;
	// Property: $condition
	// This node's condition, determines when its contained inputs are relevant.
	protected $condition = null;

	// Group: Functions
	/*
		Constructor: __construct

		Parameters:
		$type - The type of tag. If this is the empty string, this node will act as a simple
		        container for other HTML nodes. No conditions or attributes may be assigned.
	*/
	public function __construct($type) {
		$this->type = $type;
	}

	public function plainClone() {
		$result = parent::plainClone();
		$newChildren = array();
		foreach ($result->children as $child)
			$newChildren[] = $child->plainClone();
		$result->children = $newChildren;
		return $result;
	}

	public function templateClone($params) {
		$res = parent::templateClone($params);
		$newChildren = array();
		foreach ($res->children as $child)
			$newChildren[] = $child->templateClone($params);
		$res->children = $newChildren;
		return $res;
	}

	// Group: Accessors
	// Method: getType
	// Returns this node's <$type>.
	public function getType()      { return $this->type; }
	// Method: getChildren
	// Returns this node's <$children>.
	public function getChildren()  { return $this->children; }
	// Method: getCondition
	// Returns this node's <$condition>.
	public function getCondition() { return $this->condition; }
	// Method: getAttributes
	// Returns this node's <$attributes>.
	public function getAttributes() { return $this->attributes; }

	// Group: Modifiers
	/*
		Method: replaceChild.
		Replaces a child of this node with another.

		Parameters:
		$idx      - The index of the child to replace.
		$newChild - The node that replaces the child.
	*/
	public function replaceChild($idx, HTMLNode $newChild) { $this->children[$idx] = $newChild; }

	/*
		Method: addAttributes
		Adds attributes to this node.

		Parameters:
		$attribs - May be either an associative array of names/values, or a DOMNamedNodeMap,
		           which contains tag attributes in the DOM.
	*/
	public function addAttributes(/*DOMNamedNodeMap*/ $attribs) {
		if (!is_array($attribs)) {
			if (!$attribs instanceof DOMNamedNodeMap)
				throw new ParseException('Invalid argument to HTMLNode::addAttributes.');

			for ($i=0; $i<$attribs->length; $i++) {
				$node = $attribs->item($i);
				$this->attributes[$node->nodeName] = $node->nodeValue;
			}
		} else {
			$this->attributes = array_merge($this->attributes, $attribs);
		}
	}

	/*
		Method: addChildren
		Adds child nodes.

		Parameters:
		$nodes - An array of <HTMLNodes> that will be added as children.
	*/
	public function addChildren(array $nodes) {
		$this->children = array_merge($this->children, $nodes);
	}

	/*
		Method: setCondition
		Sets the condition for this node.

		Parameters:
		$cond - The new condition of this node.
	*/
	public function setCondition(Expression $cond) { $this->condition = $cond; }

	public function getHTML() {
		$innerHTML = '';
		foreach ($this->children as $child) {
			$innerHTML.= $child->getHTML();
		}

		if ($this->condition && $this->disableMethod == self::Hide) {
			$cond = new UnaryExpr('!', $this->condition);
			$condShow = '{if '.$cond->getHTML().'} style="display:none"{/if}';
		} else
			$condShow = '';

		$res = $this->makeTag($this->type, $this->attributes, $innerHTML, $condShow);
		return $res;
	}

	public function getConditionalStmt($leafCallback, $addRequired = true) {
		$res = array();
		foreach ($this->children as $child)
			$res = array_merge($res, $child->getConditionalStmt($leafCallback, $addRequired));

		if (count($res)==0)
			return $res;
		else if (count($res)==1) {
			$res = reset($res);
			if ($this->condition && $res['cond'])
				$cond = new BinaryExpr('&&', $this->condition, $res['cond']);
			else if ($this->condition)
				$cond = $this->condition;
			else
				$cond = $res['cond'];
			return array(array('cond' => $cond, 'stmt' => $res['stmt']));
		} else {
			if ($this->condition) {
				$stmt = new GroupStatement(array());
				foreach ($res as $item) {
					if ($item['cond'])
						$stmt->addStatement(new IfStatement($item['cond'], $item['stmt']));
					else
						$stmt->addStatement($item['stmt']);
				}

				return array(array('cond' => $this->condition, 'stmt' => $stmt));
			} else
				return $res;
		}
	}

	public function getConversions() {
		$res = array();
		foreach ($this->children as $child)
			$res = array_merge($res, $child->getConversions());
		return $res;
	}

	public function getDefaults() {
		$res = array();
		foreach ($this->children as $child)
			$res = array_merge($res, $child->getDefaults());
		return $res;
	}

	public function getExtraJS($js = '') {
		foreach ($this->children as $child)
			$js = $child->getExtraJS($js);
		return $js;
	}

	// Group: Internal support functions
	/*
		Method: makeTag
		Creates a HTML string for a tag.

		Parameters:
		$type          - The type of tag.
		$attributes    - An associative array of attribute names/values.
		$innerHTML	   - A string contained within the tag. If this is an empty string, the tag
		                 will also be empty (i.e., <tag />).
		$extraInnerTag - A string that will be included literally inside the start tag.
	*/
	protected function makeTag($type, $attributes, $innerHTML, $extraInnerTag = '') {
		$html = $innerHTML;

		if ($type!='') {
			$tag = '';
			foreach ($attributes as $name => $val) {
				$tag.= ' '.$name.'="'.str_replace('-&gt;', '->', htmlentities($val, ENT_COMPAT, 'UTF-8').'"');
			}
			$tag.= $extraInnerTag;
			if ($innerHTML == '')
				$tag.= ' />';
			else
				$tag.= '>';
			$tag = '<'.$type.$tag;
			$html = $tag.$html;
			if ($innerHTML != '')
				$html.= '</'.$type.'>';
		}
		return $html;
	}

}

?>