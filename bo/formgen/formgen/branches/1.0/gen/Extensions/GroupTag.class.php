<?php

require_once('DescParser.class.php');
require_once('Object.class.php');
require_once('HTML.class.php');

/*
	Class: GroupTag
	Extension tag that groups a set of tags.

	See the manual, <Group tag> for its use.
*/
class GroupTag extends ExtensionTag {
	protected $html = null;

	public function parse(DOMElement $node, DescParser &$parser) {
		$this->html = $parser->parseHTMLarray($node->childNodes, true);
	}

	public function getHTMLNodes() {
		$tag = new HTMLTag('');
		$tag->addChildren($this->html);
		return array('elements' => $tag);
	}

	public function getTemplateTagnames() { return array('elements'); }
}

DescParser::registerExtension('group', 'GroupTag');

?>