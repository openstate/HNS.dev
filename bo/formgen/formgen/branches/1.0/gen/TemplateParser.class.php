<?php

require_once('BaseParser.class.php');
require_once('Template.class.php');

/*
	Class: TemplateParser
	Parses <Form templates>.
*/
class TemplateParser extends BaseParser {
	// Property: $templates
	// A list of all templates provided by the file.
	//
	// A single tag template is an associative array with the following keys:
	// selector     - The selector for this tag
	// html         - An array with <HTMLNodes> which represent the template
	// rootOptional - Indicates that the html array has only a single element that may be
	//                removed if a single root is not required.
	protected $templates;
	protected $templateErrorOrder; // An array from template idx to self::Forward & self::Reverse

	const Forward = 0;
	const Reverse = 1;

	// Property: $templateTagNames
	// A list for each template name what tags within
	// that template will be replaced with content.
	protected $templateTagNames;

	// Property: $currTagnames
	// Holds a list of tagnames that within the template that is currently
	// being parsed will be replaced with content.
	protected $currTagnames;

	/*
		Constructor: __construct

		Parameters:
		$filename         - The XML file that contains the templates to parse.
		$templateTagNames - An array of arrays. The key of the outer array is
			the name of a template, its array value contains the names of tags
			that are placeholders for content that will be placed there later.
	*/
	public function __construct($filename, array $templateTagNames) {
		parent::__construct($filename);
		$this->templateTagNames = $templateTagNames;

		foreach ($this->xml->documentElement->childNodes as $node) {
			if ($node instanceof DOMElement) {
				$tpl = $this->parseTemplate($node);
				$this->templates[$node->nodeName] = $tpl['templates'];
				$this->templateErrorOrder[$node->nodeName] = $tpl['errorOrder'];
			}
		}
	}

	/*
		Method: hasTemplate
		Checks whether a template is defined for a certain tag within a form template.

		Parameters:
		$template - The name of the form template to look for.
		$tagname  - The name of the tag to look for within the form template.

		Returns:
		A boolean.
	*/
	public function hasTemplate($template, $tagname) {
		return isset($this->templates[$template]) && isset($this->templates[$template][$tagname]);
	}

	/*
		Method: applyTemplate
		Replaces the placeholder tags with actual content.

		Parameters:
		$template          - The form template name to use
		$subTemplate       - The tag name within the form template to use
		$selectString      - The select string to match <Selectors> against.
		$replacements      - An array, with the keys being the names of placeholder tags and their values a
		                     single tag that will replace the named placeholder tag.
		$requireSingleRoot - Indicates that the result should have a single root. This is required for example
		                     when the placeholder tag had attributes.

		Returns:
		An array of <HTMLNodes> that is the template with the placeholder tags replaced.
	*/
	public function applyTemplate($template, $subTemplate, $selectString, array $replacements, $requireSingleRoot) {
		$selTpl = null;
		if (!isset($this->templates[$template]))
			throw new ParseException('No template specified for '.$template);
		if (!isset($this->templates[$template][$subTemplate]))
			throw new ParseException('No template specified for '.$template.'.'.$subTemplate);
		$currSpecificity = -1;
		foreach ($this->templates[$template][$subTemplate] as $tpl) {
			if (preg_match('/ '.str_replace(' ', '\b.* ', $tpl['selector'], $repCount).'$/', $selectString)) {
				if ($repCount > $currSpecificity) {
					// More specific template, choose this one
					$selTpl = $tpl;
					$currSpecificity = $repCount;
				}
			}
		}
		if (!$selTpl) {
			throw new ParseException('No matching template found for template '.$template.'.'.$subTemplate.', select string \''.$selectString.'\'');
		}

		if ($requireSingleRoot && count($selTpl['html'])>1)
			throw new ParseException('No single root found but required in template '.$template.'.'.$subTemplate.', selector '.$selTpl['selector']);
		$result = array();

		foreach ($selTpl['html'] as $html) {
			$result[]= $html->templateClone($replacements);
		}
		if (!$requireSingleRoot && $selTpl['rootOptional'])
			$result = reset($result)->getChildren();

		return $result;
	}

	public function getErrorOrder($template) {
		if (!isset($this->templateErrorOrder[$template]))
			throw new ParseException('No template specified for '.$template);
		return $this->templateErrorOrder[$template];
	}

	// Supply the form template node, e.g. <createform>
	/*
		Method: parseTemplate
		Parses a single form template tag.
		Also performs inheritance of templates if necessary.

		Parameters:
		$tplNode - The DOMNode that represents the form template

		Returns:
		An array of tag templates.
	*/
	protected function parseTemplate(DOMNode $tplNode) {
		$templates = array();
		$errorOrder = self::Forward;

		// Inherit templates
		if ($tplNode->hasAttribute('inherits')) {
			$inherit = $tplNode->getAttribute('inherits');
			if (!isset($this->templates[$inherit]))
				throw new ParseException('Inheriting non-existing template \''.$inherit.'\' in '.$node->nodeName);
			foreach ($this->templates[$inherit] as $name => $templateList) {
				$templates[$name] = array();
				foreach ($templateList as $template) {
					$html = array();
					foreach ($template['html'] as $htmlSub)
						$html[]= $htmlSub->plainClone();
					$templates[$name][] = array(
						'selector'     => $template['selector'],
						'html'         => $html,
						'rootOptional' => $template['rootOptional']
					);
				}
				$errorOrder = $this->templateErrorOrder[$inherit];
			}
			$inherited = array_flip(array_keys($templates));
		} else
			$inherited = array();

		if ($tplNode->hasAttribute('errororder')) {
			$s = $tplNode->getAttribute('errororder');
			if ($s == 'forward')
				$errorOrder = self::Forward;
			else if ($s == 'reverse')
				$errorOrder = self::Reverse;
		}

		foreach ($tplNode->childNodes as $node) {
			if ($node instanceof DOMElement) { // Only parse actual tags
				$template = array('selector' => '.*', 'html' => null);
				if ($node->hasAttribute('selector'))
					$template['selector'] = $node->getAttribute('selector');

				// Determine if any direct subtags have the attribute 'optional' set, take it, and remove
				// the attribute.
				$template['rootOptional'] = false;
				$elementCount = 0;
				$textElementCount = 0;
				foreach ($node->childNodes as $sub) {
					if ($sub instanceof DOMElement && $sub->hasAttribute('optional')) {
						$sub->removeAttribute('optional');
						$template['rootOptional'] = true;
					}
				}

/*
				if (!isset($this->templateTagNames[$node->nodeName]))
					throw new ParseException('Tag '.$node->nodeName.' templated but unknown.');
*/
				if (!isset($this->templateTagNames[$node->nodeName]))
					$this->currTagnames = array('content'); // Non-special templated tag
				else
					$this->currTagnames = $this->templateTagNames[$node->nodeName];
				$template['html'] = $this->parseHTMLarray($node->childNodes, true);

				// Check if there are multiple nodes in the template, or if there is only one textnode.
				// In this case, the optional option is invalid.
				if ($template['rootOptional'])
					if (count($template['html'])>1 || reset($template['html']) instanceof HTMLText)
						throw new ParseException('Invalid use of \'optional\' in template '.$node->nodeName.' of '.$tlpNode->nodeName);

				if (isset($inherited[$node->nodeName])) {
					// If the tag template was inherited, we wipe that template now.
					unset($templates[$node->nodeName]);
					unset($inherited[$node->nodeName]);
				}
				if (!isset($templates[$node->nodeName]))
					$templates[$node->nodeName] = array();
				$templates[$node->nodeName][] = $template;
			}
		}
		return array('templates' => $templates, 'errorOrder' => $errorOrder);
	}

	/*
		Method: parseHTML
		Parses a single DOMNode. Handles a few cases specific to the template parser.
		These special cases are: The errormsg tag and the input submit.

		For parameters and return values, see <BaseParser::parseHTML>.
	*/
	public function parseHTML(DOMNode $node) {
		$tag = false;
		if ($node instanceof DOMElement) {
			if ($node->nodeName == 'errormsg') // Exception
				$tag = new ErrorPlaceholder();
			else if ($node->nodeName == 'input' && $node->hasAttribute('type') &&
				in_array($node->getAttribute('type'), array('submit', 'reset', 'button'))) {
				// Submit, reset & normal buttons: special case
				switch ($node->getAttribute('type')) {
				case 'submit': $tag = new HTMLSubmit(); break;
				case 'reset':  $tag = new HTMLReset(); break;
				case 'button': $tag = new HTMLButton(); break;
				}
				$node->removeAttribute('type');
				$tag->addAttributes($node->attributes);
			} else if (in_array($node->nodeName, $this->currTagnames))
				$tag = new HTMLTemplateNode($node->nodeName);
		}

		if (!$tag)
			$tag = parent::parseHTML($node);
		return $tag;
	}

}

?>