<?php

require_once('DescParser.class.php');
require_once('HTML.class.php');

class UseTag extends ExtensionTag {
	protected $id = false;
	protected $condition = null;

	protected $property = null;
	protected $type = '';

	protected $label, $value;

	public function parse(DOMElement $node, DescParser &$parser) {
		if (!$node->hasAttribute('property')) {
			throw new ParseException('No property given in use tag.');
		}

		$prop = $node->getAttribute('property');
		$parser->linkInputToProperty($prop, $prop);
	}

	public function getHTMLNodes() {
		return new HTMLText('');
	}

	public function getTemplateTagnames() { return false; }

	public function getID(DOMElement $node) {
		if ($node->hasAttribute('property'))
			return $node->getAttribute('property');
		else
			return false;
	}
}

DescParser::registerExtension('use', 'UseTag');

?>