<?php

class mce extends InputElement {
	protected $name = '';
	protected $value = '';

	protected $attributes = array('rows' => 8, 'cols' => 50);

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		return $this->applyTemplate('mce.html',
			array('name' => $this->state['name'], 'value' => htmlspecialchars($this->value)),
			$context);
	}

	public function getConditions() {
		$result = parent::getConditions();
		$result[] = array(
			'extraJS' => 'tinyMCE.execCommand(\'mceAddControl\', false, \''.addslashes($this->state['name']).'\');'
		);

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

	public function getJSValue() {
		return 'tinyMCE.get(\''.addslashes($this->state['name']).'\').getContent()';
	}
}

?>