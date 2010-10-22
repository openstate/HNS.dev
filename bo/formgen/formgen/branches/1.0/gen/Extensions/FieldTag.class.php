<?php

require_once('DescParser.class.php');
require_once('Object.class.php');
require_once('HTML.class.php');

/*
	Class: FieldTag
	Extension tag that describes a simple form field.

	See the manual, <Field tag> for its use.
*/
class FieldTag extends ExtensionTag {
	protected $id = false;
	protected $condition = null;

	protected $property = null;
	protected $type = '';

	protected $label, $value;

	public function parse(DOMElement $node, DescParser &$parser) {
		if ($node->hasAttribute('property') && $node->hasAttribute('id'))
			throw new ParseException('Both property name ('.$node->getAttribute('property').') and id ('.$node->getAttribute('id').') defined for field element');

		$isProperty = false;
		if ($node->hasAttribute('id')) {
			$parser->ensureUnused($node->getAttribute('id'));
			$parser->addID($node->getAttribute('id'));
			$this->id = $node->getAttribute('id');
			$isProperty = false;
		} else if ($node->hasAttribute('property')) {
			if (!$node->hasAttribute('type'))
				throw new ParseException('No type specified for property field of '.$node->getAttribute('property'));
			$this->type = $node->getAttribute('type');
			$this->id = $node->getAttribute('property');
			$this->property = $parser->getProperty($node->getAttribute('property'));
			$isProperty = true;
			$parser->linkInputToProperty($this->id, $this->id);
		}

		if ($isProperty) {
			// Field linked to property
			if ($this->type=='custom') {
				// Custom HTML
				$value = $parser->parseHTMLarray($node->childNodes, true);
				$html = new HTMLTag('');
				$html->addChildren($value);
			} else {
				$html = HTMLInputFactory::create($this->type, $this->property->getID(), $parser->getFormName());
				$html->setFromProperty($this->property);

				if ($node->hasAttribute('required')) {
					if ($node->getAttribute('required'))
						$html->makeRequired();
					else
						$html->makeOptional();

					$node->removeAttribute('required');
				}
				if ($node->hasAttribute('prefix')) {
					$html->setPrefix($node->getAttribute('prefix'));
					$node->removeAttribute('prefix');
				}
				if ($node->hasAttribute('postfix')) {
					$html->setPostfix($node->getAttribute('postfix'));
					$node->removeAttribute('postfix');
				}
				$node->removeAttribute('property');
				$node->removeAttribute('type');

				if ($node->hasAttribute('default')) {
					$html->setDefault($node->getAttribute('default'));
					$node->removeAttribute('default');
				}

				$html->addAttributes($node->attributes);
				$html->useParser($parser);
			}
			$this->label = new HTMLText($this->property->getCaption());
			$this->value = $html;
		} else {
			// Normal field with label & value
			if ($this->id)
				$id = $this->id;
			else
				$id = '(id unknown)';

			$label = $parser->getElementsByName($node, 'label');

			if (count($label)==0)
				throw new ParseException('Label missing for field '.$id);
			if (count($label)>1)
				throw new ParseException('Multiple labels given for field '.$id);
			$label = reset($label);

			$value = $parser->getElementsByName($node, 'value');
			if (count($value)==0)
				throw new ParseException('Value missing for field '.$id);
			if (count($value)>1)
				throw new ParseException('Multiple values given for field '.$id);
			$value = reset($value);

			$this->label = new HTMLTag('');
			$this->label->addChildren($parser->parseHTMLarray($label->childNodes, true));
			$this->value = new HTMLTag('');
			$this->value->addChildren($parser->parseHTMLarray($value->childNodes, true));
		}
	}

	public function getHTMLNodes() {
		return array(
			'label' => $this->label,
			'value' => $this->value
		);
	}

	public function getTemplateTagnames() { return array('label', 'value'); }

	public function getID(DOMElement $node) {
		if ($node->hasAttribute('property'))
			return $node->getAttribute('property');
		else
			return false;
	}
}

DescParser::registerExtension('field', 'FieldTag');

?>