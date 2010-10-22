<?php

class staticSelectElement extends InputElement {
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

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		if (is_array($this->source)) {
			$result = false;
			foreach ($this->source as $key => $value) {
				if ($key == $this->value) {
					$result = $value->getHtml($context);
					break;
				}
			}
			return $this->applyTemplate('staticSelect.html',
				array('value' => $this->value,
				'displayValue' => $result,
				'name' => $this->name),
				$context);
		}
		return $this->applyTemplate('staticSelect.html',
			array('value' => $this->value,
			'displayValue' => '{'.$this->source.'.'.$this->value.'}',
			'name' => $this->name),
			$context);
	}

	public function getValue() {
		return $this->value;
	}

	public function setFromData($data) {
		if (isset($data[$this->name]))
			$this->value = $data[$this->name];
	}

	public function isGiven() {
		return true;
	}
}

?>