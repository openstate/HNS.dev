<?php

class textarea extends InputElement {
	protected $name = '';
	protected $value = '';
	protected $attributes = array(
		'rows' => 5,
		'cols' => 10
	);

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		return $this->applyTemplate('textarea.html',
			array('name' => $this->state['name'], 'value' => htmlspecialchars($this->value)),
			$context);
	}

	public function getEvent($name) {
		$result = parent::getEvent($name);
		$result['name'] = 'keyup';
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
		return (bool)$this->value;
	}
}

?>