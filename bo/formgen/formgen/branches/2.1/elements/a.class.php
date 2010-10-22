<?php

class a extends InputElement {
	protected $name = '';
	protected $value = '';
	protected $content = null;

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);
		$this->content = $parser->parseNodes($node->childNodes, $context, true);
		$this->content->parent = $this;
		$this->children = array($this->content);
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		return $this->applyTemplate('a.html',
			array('exists' => $this->value ? 'true' : 'false', 'name' => $this->name, 'value' => $this->value, 'content' => $this->content),
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