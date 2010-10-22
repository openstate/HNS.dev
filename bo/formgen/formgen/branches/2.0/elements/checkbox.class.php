<?php

class checkbox extends InputElement {
	protected $name = '';
	protected $value = false;

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		return $this->applyTemplate('checkbox.html',
			array('name' => $this->state['name'], 'value' => ($this->value ? 'true' : 'false')),
			$context);
	}

	public function getValue() {
		return $this->value;
	}

	public function setFromData($data, $raw = false) {
		if ($raw) {
			if (isset($data[$this->name])) {
				$this->value = $data[$this->name];
			}
		} else {
			$this->value = isset($data[$this->name]);
		}
	}

	public function isGiven() {
		return true;
	}

	public function getJSValue() {
		return 'form[\''.addslashes($this->state['name']).'\'].checked';
	}
}

?>