<?php

class staticDate extends InputElement {
	protected $name = '';
	protected $value = '';
	protected $opts = array(
		'format'	=> 'dd MM YYYY',
		'emptyValue' => null,
		'emptyDisplay' => 'Not Set'
	);

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		if ($this->value == $this->opts['emptyValue']) {
			return $this->applyTemplate('staticDate.html',
				array('value' => $this->opts['emptyDisplay']),
				$context);
		} else {
			return $this->applyTemplate('staticDate.html',
				array('value' => '{\''.$this->value.'\'|date_format:\''.$this->opts['format'].'\'}'),
				$context);
		}
	}

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);

		foreach ($this->opts as $key => &$value) {
			if ($node->hasAttribute($key))
				$value = $node->getAttribute($key);
		}
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