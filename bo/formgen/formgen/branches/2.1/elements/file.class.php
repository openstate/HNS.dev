<?php

class file extends InputElement {
	protected $name = '';
	protected $value = array('name' => '', 'type' => '', 'size' => '', 'tmp_name' => '', 'error' => '');

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		return $this->applyTemplate('file.html',
			array('name' => $this->state['name'], 'value' => $this->value['name']),
			$context);
	}

	public function getEvent($name) {
		return array(
			'add'  => 'function(eventname, handler) { $(this).addEvent(\'change\', handler).addEvent(\'keyup\', handler) }.bind(form[\''.addslashes($name).'\'])',
			'name' => ''
		);
	}

	public function getValue() {
		return $this->value;
	}

	public function setFromData($data) {
		if (isset($data[$this->name]))
			$this->value = $data[$this->name];
	}

	public function isGiven() {
		return (bool)$this->value && isset($this->value['error']) && $this->value['error'] == UPLOAD_ERR_OK;
	}
}

?>