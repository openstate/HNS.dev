<?php

require_once 'select.class.php';

class dependentselect extends select {
	protected $dependency;
	protected $map;

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);
		if (!$node->hasAttribute('dependency'))
			throw new ParseException('DependentSelect node given without a dependency');
		elseif (!$node->hasAttribute('map'))
			throw new ParseException('DependentSelect node given without a dependency map');

		$this->dependency = $node->getAttribute('dependency');
		$this->map = $node->getAttribute('map');
	}

	public function getConditions() {
		$result = parent::getConditions();
		$result[] = array(
			'extraJS' => 'initDependentSelect(form, \''.addslashes($this->state['name']).'\', \''.addslashes($this->dependency).'\', {/literal}{'.$this->map.'|@json_encode}{literal});'
		);

		return $result;
	}
}

?>