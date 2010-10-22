<?php

require_once('DescParser.class.php');
require_once('HTML.class.php');

/*
	Class: InputTag
	Extension tag that handles input tags.

	Instead of simply using HTML input tags, we catch the input tag and use it
	to refer to more advanced <Inputs>. This extension tag processes the
	input tag and passes the checks, values and the required option to the
	created tag.
*/
class RawInputTag extends ExtensionTag {
	protected $input;

	public function parse(DOMElement $node, DescParser &$parser) {
		$tag = new HTMLTag('input');
		$tag->addAttributes($node->attributes); // Add remaining attrs
		$this->input = $tag;
	}

	public function getHTMLNodes() {
		return $this->input;
	}

	public function getTemplateTagnames() { return false; }
}

DescParser::registerExtension('rawinput', 'RawInputTag');

?>