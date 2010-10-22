<?php

class InEnumCheck extends Check {
	// For multiOptions
	protected $validOptions = array('values' => true);
	protected $values;
	protected $defaultTarget = 'target';
	protected $targets = array('target' => null);

	public function setValues($values) {
		$this->values = $values;
	}

	public function getExpr() {
		return new ValidEnumExpr(new InputExpr($this->targets['target']), $this->values);
	}

	public function valid($callbacks) {
		$val = $this->targets['target']->getValue();
		return is_array($val) ? !count(array_diff($val, $this->values)): in_array($val, $this->values);
	}
}

abstract class multiOption extends InputElement {
	protected $name = '';
	protected $value = '';
	protected $source = '';

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);

		// Check for in-form values
		foreach ($node->childNodes as $child) {
			if ($child instanceof DOMElement && $child->tagName == 'values') {
				$this->source = $this->getValues($child, $parser, $context);
				$node->removeChild($child);
				break;
			}
		}

		if (!$this->source) {
			if (!$node->hasAttribute('values'))
				throw new ParseException(get_class($this).' node given without a values source');
			$this->source = $node->getAttribute('values');
		}

		if (is_array($this->source)) {
			$err = require(dirname(__FILE__).'/../config/errors.inc.php');
			$check = new InEnumCheck($err['server'], $this);
			$check->setValues(array_keys($this->source));
			$context->checks[] = $check;
		}
	}

	protected function getValues(DOMElement $node, $parser, ParseContext $context) {
		$result = array();
		foreach ($node->childNodes as $value) {
			if ($value instanceof DOMElement) {
				if ($value->tagName != 'value')
					throw new ParseException('Invalid tag in '.get_class($this).' values: '.$value->tagName);
				if (!$value->hasAttribute('value'))
					throw new ParseException('No value attribute given for value in '.get_class($this));

				$result[$value->getAttribute('value')] = $parser->parseNodes($value->childNodes, $context, true);
			}
		}
		return $result;
	}

	public function getValue() {
		return $this->value;
	}

	public function setFromData($data) {
		if (isset($data[$this->name]))
			$this->value = $data[$this->name];
	}

	public function isGiven() {
		return $this->value !== '';
	}
}

?>