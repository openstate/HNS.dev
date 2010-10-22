<?php

class staticElement extends InputElement {
	protected $name = '';
	protected $value = '';
	protected $default = '';

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);
		if ($node->hasAttribute('default'))
			$this->default = $node->getAttribute('default');
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		return $this->applyTemplate('static.html',
			array('value' => $this->value ? $this->value : $this->default),
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