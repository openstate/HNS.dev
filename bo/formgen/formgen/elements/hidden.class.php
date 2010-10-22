<?php

class hidden extends InputElement {
	protected $name = '';
	protected $value = '';

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		return $this->applyTemplate('hidden.html',
			array('name' => $this->state['name'], 'value' => htmlspecialchars($this->value)),
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
		return (bool)$this->value;
	}
}

?>