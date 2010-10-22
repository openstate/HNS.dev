<?php

require_once('DescParser.class.php');
require_once('Object.class.php');
//require_once('HTML.class.php');
require_once('Check.class.php');

/*
	Class: InputTag
	Extension tag that handles input tags.

	Instead of simply using HTML input tags, we catch the input tag and use it
	to refer to more advanced <Inputs>. This extension tag processes the
	input tag and passes the checks, values and the required option to the
	created tag.
*/
class InputTag extends ExtensionTag {
	protected $input;

	public function parse(DOMElement $node, DescParser &$parser) {
		if (!$node->hasAttribute('type'))
			throw new ParseException('Input tag given without type');

		if (in_array($node->getAttribute('type'),  array('submit', 'reset', 'button'))) {
			// Submit, reset & normal buttons: special case
			switch ($node->getAttribute('type')) {
			case 'submit': $this->input = new HTMLSubmit(); break;
			case 'reset':  $this->input = new HTMLReset(); break;
			case 'button': $this->input = new HTMLButton(); break;
			}
			$node->removeAttribute('type');
			$this->input->addAttributes($node->attributes);
			return;
		}

		if (!$node->hasAttribute('id') && !$node->hasAttribute('property'))
			throw new ParseException('Input tag given without id');

		if ($node->hasAttribute('property')) {
			$id = $node->getAttribute('property');
			$property = $parser->getProperty($node->getAttribute('property'));
			$parser->linkInputToProperty($id, $id);
			$isProperty = true;
		} else {
			$parser->ensureUnused($node->getAttribute('id'));
			$parser->addID($node->getAttribute('id'));
			$id = $node->getAttribute('id');
			$isProperty = false;
		}

		// Translate special attributes
		$tag = HTMLInputFactory::create($node->getAttribute('type'), $id, $parser->getFormName());

		if ($isProperty)
			$tag->setFromProperty($property);

		$node->removeAttribute('type');
		$node->removeAttribute('id');
		$node->removeAttribute('property');

		if ($node->hasAttribute('required')) {
			if ($node->getAttribute('required'))
				$tag->makeRequired();
			else
				$tag->makeOptional();

			$node->removeAttribute('required');
		}

		// Read check & value tags
		foreach ($node->childNodes as $child) {
			if ($child->nodeName == 'check')
				$tag->addCheck(self::parseCheck($child, $parser));
			else if ($child->nodeName == 'value')
				$tag->addValue(self::parseInputValue($child, $parser));
			else if ($child->nodeName == 'customvalues') {
				if (!$child->hasAttribute('options'))
					throw new ParseException('Incomplete customvalues declaration: missing attribute \'options\'');
				$tag->setEnum(new CustomEnum($child->getAttribute('options'), $child->getAttribute('single'), $child->getAttribute('check')));
				if ($child->hasAttribute('single'))
					$parser->linkInputToProperty($child->getAttribute('single'), $child->getAttribute('single'));
			} else if ($child instanceof DOMElement)
				throw new ParseException('Invalid tag within input: '.$child->nodeName);
		}

		if ($node->hasAttribute('default')) {
			$tag->setDefault($node->getAttribute('default'));
			$node->removeAttribute('default');
		}

		$tag->addAttributes($node->attributes); // Add remaining attrs
		$this->input = $tag;
		$this->input->useParser($parser);
	}

//-
	public static function parseCheck(DOMElement $node, DescParser $parser) {
		if (!$node->hasAttribute('type'))
			throw new ParseException('Check tag given without type');
		$check = CheckFactory::create($node->getAttribute('type'), $parser);
		foreach ($node->childNodes as $child) {
			if ($child instanceof DOMElement) {
				if ($child->nodeName=='error') {
					// Custom error message
					$tag = new HTMLTag('');
					$tag->addChildren($parser->parseHTMLarray($child->childNodes));
					$check->setCustomError($tag);
				} else {
					if ($child->nodeName!='option')
						throw new ParseException('Invalid tag within check: '.$child->nodeName);
					if (!$child->hasAttribute('name'))
						throw new ParseException('Check option without name');
					$check->addOption($child->getAttribute('name'), $child->nodeValue);
				}
			}
		}
		return $check;
	}
//-
	public static function parseInputValue(DOMElement $node, DescParser &$parser) {
		if (!$node->hasAttribute('value'))
			throw new Exception('Value tag given without value');
		$parent = new HTMLTag('');
		$parent->addChildren($parser->parseHTMLarray($node->childNodes));
		$value = new Value($node->getAttribute('value'), $parent);
		return $value;
	}

	public function getHTMLNodes() {
		return $this->input;
	}

	public function getTemplateTagnames() { return false; }
}

DescParser::registerExtension('input', 'InputTag');

?>