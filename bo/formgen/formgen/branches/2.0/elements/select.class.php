<?php

require_once('multiOption.class.php');

class select extends multiOption {
	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		if (is_array($this->source)) {
			$source = '{php}$this->_tpl_vars[\'sourceOptions\'] = array(';
			foreach ($this->source as $key => $value)
				$source .= '\''.addslashes($key).'\' => \''.addslashes($value->getHtml($context)).'\',';
			$source .= ');{/php}';
		} else
			$source = '';
		return $this->applyTemplate('select.html',
			array(
				'name' => $this->state['name'], 'value' => addcslashes($this->value, '\\\''),
				'presource' => $source, 'source' => is_array($this->source) ? '$sourceOptions' : $this->source
			),
			$context);
	}
}

?>