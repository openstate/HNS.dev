<?php

require_once('multiOption.class.php');

class multiCheckbox extends multiOption {
	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		if (is_array($this->source)) {
			$source = '{php}$this->_tpl_vars[\'sourceOptions\'] = array(';
			foreach ($this->source as $key => $value)
				$source .= '\''.addslashes($key).'\' => \''.addslashes($value->getHtml($context)).'\',';
			$source .= ');{/php}';
		} else
			$source = '';
		return $this->applyTemplate('multiCheckbox.html',
			array(
				'name' => $this->state['name'], 'value' => is_array($this->value) ? implode('||', $this->value) : $this->value,
				'presource' => $source, 'source' => is_array($this->source) ? '$sourceOptions' : $this->source
			),
			$context);
	}

	public function getEvent($name) {
		return array(
			'add'  => 'function(eventname, handler) { bindRadioEvent(this[\''.addslashes($name).'[]\'], eventname, handler); }.bind(form)',
			'name' => 'click'
		);
	}

	public function getJSValue() {
		return 'getMultiCheckValue(form[\''.addslashes($this->state['name']).'[]\'])';
	}
}

?>