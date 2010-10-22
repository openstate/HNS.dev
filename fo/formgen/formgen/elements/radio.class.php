<?php

require_once('multiOption.class.php');

class radio extends multiOption {
	protected $separator = '<br />';

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		foreach ($node->childNodes as $child) {
			if ($child instanceof DOMElement && $child->tagName == 'separator') {
				$this->separator = $parser->parseNodes($child->childNodes, $context, true);
				$node->removeChild($child);
				break;
			}
		}
		parent::parse($node, $parser, $context);
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		if (is_array($this->source)) {
			$source = '{php}$this->_tpl_vars[\'sourceOptions\'] = array(';
			foreach ($this->source as $key => $value)
				$source .= '\''.addslashes($key).'\' => \''.addslashes($value->getHtml($context)).'\',';
			$source .= ');{/php}';
		} else
			$source = '';
		return $this->applyTemplate('radio.html',
			array(
				'name' => $this->state['name'], 'value' => $this->value, 'separator' => $this->separator,
				'presource' => $source, 'source' => is_array($this->source) ? '$sourceOptions' : $this->source
			),
			$context);
	}

	public function getEvent($name) {
		return array(
			'add'  => 'function(eventname, handler) { bindRadioEvent(this[\''.addslashes($name).'\'], eventname, handler); }.bind(form)',
			'name' => 'click'
		);
	}

	public function getJSValue() {
		return 'getRadioValue(form[\''.addslashes($this->state['name']).'\'])';
	}
}

?>