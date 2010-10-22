<?php

require_once('expressions/CustomExpressions.class.php');

interface InputValue {
	public function getValue();
	public function getAllValues();
	public function getName();
	public function getFullName();
	public function isGiven();    // isset would be nicer, but is a language construct.
	public function getChildValues(); // Returns an array of InputValues.
	public function getInputElement();

	public function getJSValue();  // Return a JS expression which represents this input's value
}

class SimpleInputValue implements InputValue {
	protected $owner;
	protected $name;
	protected $value;
	protected $jsCallback;

	public function __construct($owner, $name, &$value, $jsCallback) {
		$this->owner = $owner;
		$this->name  = $name;
		$this->value = &$value;
		$this->jsCallback = $jsCallback;
	}

	public function getValue() { return $this->value; }
	public function getAllValues() {
		// TODO: This is a copy of InputElement::getAllValues. Can we refactor this?
		$childValues = $this->getChildValues();
		if (count($childValues) > 0) {
			$result = array();
			foreach ($childValues as $child) {
				foreach ($child->getAllValues() as $key => $val)
					$result[$this->name][$key] = $val;
			}
		} else {
			$result = array($this->name => $this->getValue());
		}
		return $result;
	}
	public function getName()  { return $this->name;  }
	public function getFullName() { return $this->owner->getFullName().'.'.$this->name; }
	public function isGiven()  { return $this->value != ''; }
	public function getChildValues()  { return array(); }
	public function getInputElement() { return $this->owner->getInputElement(); }

	public function getJSValue() {
		if (count($this->jsCallback) > 1) {
			$data = $this->jsCallback;
			$data[0] = $this;
			return call_user_func_array($this->jsCallback[0], $data);
		} else
			return call_user_func($this->jsCallback[0], $this);
	}
}

abstract class InputElement extends Element implements InputValue {
	public $marked = false;

	protected $required = false;

	protected $errorPosition = null;

	protected $name = '';

	public function setRequired($req) { $this->required = $req; }

	public function getCondition() {
		$c = parent::getCondition();
		if (!$this->required) {
			$given = new IsGivenExpr(new InputExpr($this));
			if ($c)
				$c = new BinaryExpr('&&', $c, $given);
			else
				$c = $given;
		}
		return $c;
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		$this->state['name'] = $context->makeName($this->name);
		$this->errorPosition = $context->closestError;
	}

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);
		if (!$node->hasAttribute('name'))
			throw new ParseException(ucfirst(get_class($this)).' node given without name');
		$this->name = $node->getAttribute('name');
		$this->state['name'] = $this->name;
	}

	public function getErrorPosition() { return $this->errorPosition; }

	public abstract function setFromData($data);

	public function getAllValues() {
		$childValues = $this->getChildValues();
		if (count($childValues) > 0) {
			$result = array();
			foreach ($childValues as $child) {
				foreach ($child->getAllValues() as $key => $val)
					$result[$this->name][$key] = $val;
			}
		} else {
			$result = array($this->name => $this->getValue());
		}
		return $result;
	}

	public function getEvent($name) {
		return array('add' => '$(form[\''.addslashes($name).'\']).addEvent.bind(form[\''.addslashes($name).'\'])', 'name' => 'change');
	}

	public function isAllRequired() {
		return $this->required;
	}

	// InputValue
	public function getName() { return $this->state['name']; }
	public function getFullName() { return $this->state['name']; }
	public function getChildValues() { return array(); }
	public function getInputElement() { return $this; }
	public function getJSValue() {
		return 'form[\''.addslashes($this->state['name']).'\'].value';
	}
}

?>