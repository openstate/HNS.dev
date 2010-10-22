<?php

class staticElement extends InputElement {
	protected $name = '';
	protected $value = '';

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		return $this->applyTemplate('static.html',
			array('value' => $this->value),
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