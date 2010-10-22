<?php

/*
	File: Html classes
	Contains a number of classes to represent basic Html nodes.
*/
/*
require_once('Check.class.php');
require_once('Expression.class.php');
require_once('Statement.class.php');
*/

class HtmlContext {
	public $closestError    = null;
	public $errorPositions  = array();
	public $errorOffset     = 0;
	public $namePrefix      = '', $namePostfix = ''; // These values should be put in front of / behind an inputs name.
	public $namedLocs       = array();
	public $unmatchedChecks = array();
	public $idPrefix        = '';
	public $checks          = array();

	// Convenience function.
	public function makeName($name) {
		return $this->namePrefix.$name.$this->namePostfix;
	}

	public function getCopy() {
		$result = clone $this;
		$result->errorPositions = &$this->errorPositions;
		$result->checks         = &$this->checks;
		return $result;
	}

	protected function sortErrors($a, $b) {
		return $b->start - $a->start;
	}

	public function resolveCheckLocs($checks, &$html) {
		foreach ($checks as $c) {
			$named = $c->getNamedPosition();
			if ($named) {
				if (!isset($this->namedLocs[$named]))
					throw new ParseException('Unknown named error message location: '.$named);
				$this->namedLocs[$named]->addCheck($c);
			} else {
				$pos = $c->getFirstTarget()->getInputElement()->getErrorPosition();
				if ($pos) {
					$pos->addCheck($c);
				} else {
					$this->unmatchedChecks[] = $c;
				}
			}
		}

		usort($this->errorPositions, array($this, 'sortErrors'));
		foreach ($this->errorPositions as $e) {
			$e->setupHtml($html);
		}
	}
}

/*
	Class: HtmlNode
	Base class for all Html nodes.
*/
abstract class HtmlNode {
	protected static $autoName = 0;

	protected $parent = null;
	protected $children = array();
	protected $condition = null;
	protected $condMethod = false;
	protected $id = '';

	protected $state = array();

	public function __construct() {}

	/*
		Method: getHtml
		Returns a string representation of the Html of this node.
	*/
	public function getHtml(HtmlContext $context) {
		$this->state['idPrefix'] = $context->idPrefix;
	}

	public function setCondition(Expression $condition, $method = null) {
		$this->condition = $condition;
		$this->condMethod = $method ? $method : 'hide';
		if (!$this->id)
			$this->id = 'elem'.self::$autoName++;
	}

	public function getCondition() {
		if ($this->parent)
			$p = $this->parent->getCondition();
		else
			$p = null;

		if ($p && $this->condition)
			return new BinaryExpr('&&', $p, $this->condition);
		else if ($p)
			return $p;
		else
			return $this->condition;
	}

	public function getConditions() {
		$result = array();
		foreach ($this->children as $child)
			$result = array_merge($result, $child->getConditions());

		if ($this->condition) {
			$result[] = array(
				'id'           => $this->state['idPrefix'].$this->id,
				'condition'    => $this->condition->getJS(0),
				'method'       => $this->condMethod,
				'targets'      => $this->getTargetNames($this->condition->getTargets()),
				'checkTargets' => array()
			);
		}

		return $result;
	}

	protected function getTargetNames($targets) {
		$result = array();
		foreach ($targets as $t)
			$result[] = array('name' => $t->getName(), 'target' => $t);
		return $result;
	}

	public function getAllChecks() {
		$result = array();
		foreach ($this->children as $c)
			$result = array_merge($checks, $c->getAllChecks());
		return $result;
	}

	public function getState() {
		return $this->state;
	}

	public function setState($state) {
		$this->state = $state;
	}

	public function isAllRequired() {
		$hasRequired = false;
		foreach ($this->children as $c) {
			$res = $c->isAllRequired();
			if ($res === false)
				return false;
			else if ($res)
				$hasRequired = true;
		}

		if ($hasRequired)
			return true;
		else
			return null;
	}

	protected function findFirstErrorLoc($node) {
		if ($node instanceof ErrorNode)
			return $node->getError();
		else {
			foreach ($node->children as $child) {
				$er = $this->findFirstErrorLoc($child);
				if ($er)
					return $er;
			}
		}
		return null;
	}

}

class ErrorNode extends HtmlNode {
	protected $error, $next, $useNext;
	protected $isCloned = false;
	protected $errorClone = null;

	public function __construct(ErrorMsg $error, $next) {
		parent::__construct();
		$this->error = $error;
		$this->next = $next;
		$this->useNext = true;
	}

	public function setUseNext($use) {
		$this->useNext = $use;
	}

	public function getError() { return $this->getErrorClone(); }

	/*
		Note: The errorClone construct is a somewhat arcane method to make sure error nodes behave
		properly within array elements. This is due to array elements needing unique ErrorMsg instances
		for each run (as is done via templated error positions), yet ErrorNodes used to hold a single
		instance of ErrorMsg that was reused.
	*/
	protected function getErrorClone() {
		$this->isCloned = true;
		$this->errorClone = clone $this->error;
		return $this->errorClone;
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		if (!$this->isCloned)
			$this->errorClone = clone $this->error;
		$this->isCloned = false;

		$context->errorPositions[] = $this->errorClone;
		$this->errorClone->offset($context->errorOffset);
		if ($this->useNext && $this->next)
			$context->closestError = $this->next->getErrorClone();
		else
			$context->closestError = $this->errorClone;

		return '';
	}
}

/*
	Class: HtmlText
	Holds plain text.
	Html entities are automatically generated for special characters.
*/
class HtmlText extends HtmlNode {
	protected $text = '';
	protected $encode = true;

	/*
		Constructor: __construct

		Parameters:
		$text - The text contained in this node. Should not contain Html entities.
	*/
	public function __construct($text, $encode = true) {
		parent::__construct();
		$this->text = $text;
		$this->encode = $encode;
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		if ($this->encode)
			$text = htmlentities($this->text, ENT_COMPAT, 'UTF-8');
		else
			$text = $this->text;
		$context->errorOffset += strlen($text);
		return $text;
	}
}

/*
  Class: HtmlTag
	General representation of Html nodes.
	This class is generally sufficient for holding Html nodes.
*/
class HtmlTag extends HtmlNode {
	// Group: Properties
	// Property: $tagName
	// The name of the tag, e.g. 'table' or 'em'. May also be the empty string.
	protected $tagName = '';
	// Property: $attributes
	// An associative array of attribute names and their values.
	protected $attributes = array();

	// Group: Functions
	/*
		Constructor: __construct

		Parameters:
		$type - The type of tag. If this is the empty string, this node will act as a simple
		        container for other HTML nodes. No conditions or attributes may be assigned.
	*/
	public function __construct($tagName) {
		parent::__construct();
		$this->tagName = $tagName;
	}

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
				throw new ParseException('Invalid argument to HtmlNode::addAttributes.');

			for ($i = 0; $i < $attribs->length; $i++) {
				$node = $attribs->item($i);
				$this->attributes[$node->nodeName] = $node->nodeValue;
			}
		} else {
			$this->attributes = array_merge($this->attributes, $attribs);
		}
		if (isset($this->attributes['id']))
			$this->id = $this->attributes['id'];
	}

	/*
		Method: addChildren
		Adds child nodes.

		Parameters:
		$nodes - An array of <HtmlNodes> that will be added as children.
	*/
	public function addChildren(array $nodes) {
		foreach ($nodes as $n)
			$n->parent = $this;
		$this->children = array_merge($this->children, $nodes);
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		if ($this->id)
			$this->attributes['id'] = $context->idPrefix.$this->id;

		$innerHtml = $this->startTag($this->tagName, $this->attributes, count($this->children) > 0);
		$context->errorOffset += strlen($innerHtml);
		foreach ($this->children as $child) {
			$html = $child->getHtml($context);
			$innerHtml .= $html;
		}

		$html = $this->endTag($this->tagName, count($this->children) > 0);
		$context->errorOffset += strlen($html);

		return $innerHtml.$html;
	}

	// Group: Internal support functions
	/*
		Method: makeTag
		Creates a Html string for a tag.

		Parameters:
		$type          - The type of tag.
		$attributes    - An associative array of attribute names/values.
		$innerHtml	   - A string contained within the tag. If this is an empty string, the tag
		                 will also be empty (i.e., <tag />).
		$extraInnerTag - A string that will be included literally inside the start tag.
	*/
	protected function startTag($tagName, $attributes, $hasInner) {
		if ($tagName != '') {
			$tag = '<'.$tagName;
			foreach ($attributes as $name => $val) {
				$tag .= ' '.$name.'="'.htmlentities($val, ENT_COMPAT, 'UTF-8').'"'; //str_replace('-&gt;', '->', htmlentities($val, ENT_COMPAT, 'UTF-8')).'"';
			}
			if ($hasInner)
				$tag .= '>';
			else
				$tag .= ' />';

			return $tag;
		} else
			return '';
	}

	protected function endTag($tagName, $hasInner) {
		if ($tagName != '' && $hasInner)
			return '</'.$tagName.'>';
		else
			return '';
	}

}

?>