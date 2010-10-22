<?php

class img extends InputElement {
	protected $name = '';
	protected $value = '';
	protected $alt = '';

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);
		if (!$node->hasAttribute('alt'))
			throw new ParseException('Alt attribute required for node img');

		$this->alt = $node->getAttribute('alt');
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		return $this->applyTemplate('img.html',
			array('exists' => $this->value ? 'true' : 'false', 'name' => $this->name, 'value' => $this->value, 'alt' => $this->alt),
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