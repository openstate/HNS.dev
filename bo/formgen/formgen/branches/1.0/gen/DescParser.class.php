<?php

// File: DescriptionParser

require_once('BaseParser.class.php');
require_once('ExprParser.class.php');

require_once('Object.class.php');
require_once('HTML.class.php');
require_once('HTMLInput.class.php');

require_once('Template.class.php');

/*
	Class: ExtensionTag
  Describes <Extension tags> for the form description parser.
  Extend this class and implement the abstract functions (and override the nonabstracts
	if necessary) to define new tag functionality. Also register the new tag with
	<DescParser::registerExtension>.
*/
abstract class ExtensionTag {
  /*
  	Method: parse
  	Parses the extension tag.

		Parameters:
		$node   - a DOMElement of the tag.
		$parser - an instance of the <DescParser> that has found this tag.

		This function is responsible for parsing the extension tag.	It can use the
		<DescParser::Parser support> functions for some common functionality, such as
		parsing free HTML content contained within a subtag.

		Note that this function does not return anything.
	*/
	abstract public function parse(DOMElement $node, DescParser &$parser);

	/*
		Method: getHTMLNodes
		Returns an array with the single HTML nodes that should replace the template tags.
		Key indices are the names from getTemplateTagnames.

		Returns:
		The described array, unless the result of <getTemplateTagnames> is *false*, then it
		returns a single HTML node.
	*/
	abstract public function getHTMLNodes();

	/*
		Method: getTemplateTagnames
		Specifies the placeholder tag names in the template for this tag.

		Returns:
		An array of strings, specifying the names of tag at which point HTML should be inserted.
		Can also return *false*, this indicates that no template is used.
	*/
	abstract public function getTemplateTagnames();

	/*
		Method: getExtraTemplateTags
    Returns extra template tagnames that are used by this extension tag.
		Normally, only a template with the same name as the extension tag is defined by
		an extension. Some extensions may need more templates, so they can specify
		the names and placeholder tags through this function.

		Returns:
		An array where the keys are template names, and the values arrays with placeholder
		tag names.
	*/
	public function getExtraTemplateTags() { return array(); }

	/*
		Method: getID
	  Returns the id of the tag.

		Some extension tags may use other attributes to determine their ids (An example is
		the field tag, which may use the attribute *property*). If other attributes are
		used, override this method to return the id of an extension tag.

		Parameters:
		$node - The node to determine the id of

		Returns:
		The id of the node, or *false* if this node does not have an id, or it uses the *id* attribute
		for ids.
	*/
	public function getID(DOMElement $node) { return false; }

	/*
		Method: wrapHTML
		Called after templates have been applied.
		This function allows the extension tag to modify HTML tags that are part of the applied
		template.

		Parameters:
		$html - A <HTMLNode> representing the generated html.

		Returns:
		The replacement html.
	*/
	public function wrapHTML(HTMLNode $html) { return $html; }
}

/*
	Class: DescParser
	The form description parser.
	This parser parses <Form descriptions>.
*/
class DescParser extends BaseParser {
	// Group: Properties
	// Property: $descriptions
	// An array of all the descriptions in the file
	private $descriptions = array();

	// Property: $templateParser
	// An instance of a <TemplateParser> for the specified template
	private $templateParser = null;

	// Property: $templateTags
	// An array of all the placeholder tags for all the templates specified by <Extension tags>.
	private $templateTags = array();

	// Property: $usedIDs
	// An array of all IDs that are used within a single form description.
	// Used to determine if an id has already been used.
	private $usedIDs = array();

	// Property: $obj
	// An <Object> instance made from this file's <Object description>.
	private $obj = null;

	// Property: $extensions
	// An array with classnames of <Extension tags>.
	static private $extensions = array();

	// Group: Parse state
	// Properties that contain state that needs to be kept during parsing.

	// Property: $selectString
	// The string against which <Selectors> are matched.
	// This string basically represents the hierarchy of nodes we visited to get here, with the
	// tag names separated by spaces. It always starts with a space.
	private $selectString = ' ';

	// Property: $inputLinks
	// Holds a map from object property ids to <HTMLInput> nodes that modify these properties.
	private $inputLinks;

	// Property: $openInputs
	// Contains a list of <HTMLInput> tags that have not been	assigned an error position yet.
	// This is used in a post-parsing step that assigns inputs to an error positions.
	private $openInputs;

	// Property: $errorFlags
	// An array of all error flag names
	// Collected in a post-parsing step.
	private $errorFlags = array();

	// Property: $idToInput
	// A map from input ids to their actual <HTMLInput> nodes.
	private $idToInput;

	// Property: $submits
	// A map from submit ids to the name of their actions.
	private $submits;

	private $formID;
	private $formName;

	// Group: Functions
	/*
		Constructor: __construct
		Creates a new DescParser.

		Initializing a DescParser consists of including all extension .php files in a few
		subdirectories:

		Extensions/ - <Extension tags>
		Checks/     - <Checks>
		Inputs/     - <Inputs>

		It then initializes <$templateTags>.
	*/
	public function __construct() {
		// Find extensions
		foreach (glob(dirname(__FILE__).'/Extensions/*.php') as $file)
			require_once($file);
		// Find extensions
		foreach (glob(dirname(__FILE__).'/Checks/*.php') as $file)
			require_once($file);
		// Find extensions
		foreach (glob(dirname(__FILE__).'/Inputs/*.php') as $file)
			require_once($file);

		$extTags = array();
		$extraTags = array();
		foreach (self::$extensions as $extName => $ext) {
			$class = new $ext();
			$extTags[$extName] = $class->getTemplateTagnames();
			$extraTags = array_merge($extraTags, $class->getExtraTemplateTags());
		}
		$extTags['form'] = array('fields');
		$extTags['errormsg'] = array('msg');
		$this->templateTags = array_merge($extTags, $extraTags);
	}

	/*
		Method: registerExtension
		Registers an extension tag.

		Parameters:
		$tagname   - The name of the tag.
		$classname - The name of the class that processes the given tag.
	*/
	public static function registerExtension($tagname, $classname) {
		self::$extensions[$tagname] = $classname;
	}

	/*
		Method: setFile
		Specifies a <Form description> file from which form descriptions should be fetched.

		Parameters:
		$filename - The filename to parse.
	*/
	public function setFile($filename) {
		$xml = file_get_contents($filename);
		$xml = '<?xml version="1.0" encoding="ISO-8859-1" ?><!DOCTYPE html SYSTEM "file:///'.str_replace('\\', '/', dirname(__FILE__)).'/xhtml.ent">'.$xml;

		$this->xml = new DOMDocument();
		$this->xml->resolveExternals = true;
		$this->xml->substituteEntities = true;
		if (!$this->xml->loadXML($xml))
			throw new ParseException('Failed reading XML file ('.$filename.')');

		if (!$this->xml->documentElement->hasAttribute('template'))
			throw new ParseException('No template given');

		$this->templateParser = new TemplateParser(
			$this->xml->documentElement->getAttribute('template'),
			$this->templateTags
		);

		$baseNodes = array();
		foreach ($this->xml->documentElement->childNodes as $child) {
			if ($child instanceof DOMElement)
				$baseNodes[]= $child;
		}

		$this->descriptions = array();
		foreach ($baseNodes as $node) {
			if ($node->nodeName!='object') {
				$name = $node->nodeName;
				$formName = ($node->hasAttribute('formname') ? $node->getAttribute('formname') : false);
				if ($node->hasAttribute('inherits')) {
					$inherit = $node->getAttribute('inherits');
					if (!isset($this->descriptions[$inherit]))
						throw new ParseException($node->nodeName.' inherits unknown template '.$inherit);
					$node = $this->inheritDesc($origNodes[$inherit], $node);
				}
				$this->descriptions[$name] = array('desc' => $node->childNodes, 'formname' => $formName);
				$origNodes[$name] = $node;
			}
		}

		$this->obj = $this->parseObjectDescription();
	}

	/*
		Method: parseObjectDescription
		Parses the <Object description> in the file.

		Returns:
		An <Object> parsed from the XML's *object* tag.
	*/
	protected function parseObjectDescription() {
		$objTag = $this->getElementsByName($this->xml->documentElement, 'object');
		if (count($objTag)==0) {
			// No object
			return new Object('Form', null);
		}
		$objxml = simplexml_import_dom(reset($objTag));
		if (!isset($objxml->id) || !isset($objxml->id['name']))
			throw new ParseException('ID not given for object description');
		if (!isset($objxml['name']) || $objxml['name']=='')
			throw new ParseException('Name not given for object description');
		$obj = new Object((string)$objxml['name'], (string)$objxml->id['name']);

		foreach ($objxml->property as $prop) {
			// Parse property
			if (!isset($prop['name']))
				throw new ParseException('Missing property name');

			$read  = isset($prop['read'])  ? (string)$prop['read']  : false;
			$write = isset($prop['write']) ? (string)$prop['write'] : false;

			$p = new Property((string)$prop['name'], $read, $write);

			if (isset($prop->caption))
				$p->setCaption((string)$prop->caption);
			if (isset($prop['required'])) {
				if ((string)$prop['required'])
					$p->makeRequired();
				else
					$p->makeOptional();
			}

			// It's not that pretty to use InputTag functions here, but it does clean up the parser itself
			foreach ($prop->value as $val)
				$p->addValue(InputTag::parseInputValue(dom_import_simplexml($val), $this));
			if (isset($prop->customvalues)) {
				if (!isset($prop->customvalues['options']))
					throw new ParseException('Incomplete customvalues declaration: missing attribute \'options\'');
				$p->setCustomValues($prop->customvalues['options'], $prop->customvalues['single'], $prop->customvalues['check']);
				$this->addID((string)$prop->customvalues['single']);
			}
			foreach ($prop->check as $chk)
				$p->addCheck(InputTag::parseCheck(dom_import_simplexml($chk), $this));

			$obj->addProperty($p);
		}
		return $obj;
	}

	/*
		Method: getObjectDescription
		Returns the <Object description> of the form.
	*/
	public function getObjectDescription() { return $this->obj; }

	/*
		Method: getDescribedForms
		Returns an array with the names of the form descriptions contained in the file.
	*/
	public function getDescribedForms() {
		return array_keys($this->descriptions);
	}

	/*
		Method: getFormDescription
		Returns the data for one single form description.

		Parameters:
		$formID   - The name of the form to retrieve.
		$formName - The name of the form in the DOM.

		Returns:
		An array with the following keys:

		html       - The <HTMLNodes> describing the form's html.
		errorflags - A list of all the names of error flags used in the form.
		inputLinks - A map from	object property ids to inputs using those IDs.
		submits    - A list of all submit buttons giving the action for each submit.
	*/
	public function getFormDescription($formID, $formName) {
		if (!isset($this->descriptions[$formID]))
			throw new ParseException('Unknown form description requested: '.$formID);

		$this->usedIDs = array_flip($this->obj->getPropertyNames());
		$this->formID = $formID;
		$this->inputLinks = array();
		$this->formName = $formName;

		$formContent = new HTMLTag('');
		$formContent->addChildren($this->parseHTMLarray($this->descriptions[$formID]['desc'], true));

		$form = new HTMLTag('');
		$form->addChildren($this->templateParser->applyTemplate(
			$this->formID,
			'form', // Node name
			' ',
			array('fields' => $formContent),
			false   // require single root?
		));

		$this->openInputs = array();
		$this->errorFlags = array();
		$this->submits    = array();

		$this->processErrorMsg($form, array());
		$this->idToInput = $this->getInputs($form);
		$this->parseConditions($form);

		return array('html' => $form, 'errorflags' => $this->errorFlags, 'inputLinks' => $this->inputLinks, 'submits' => $this->submits,
		  'formname' => $this->descriptions[$formID]['formname']);
	}

	/*
		Method: getIDs
		Returns a list of all ids used in the given node and its subnodes.

		Parameters:
		$node - The node to retrieve ids from.

		Returns:
		An array of ids.
	*/
	protected function getIDs(DOMNode $node) {
		$result = array();
		if ($node instanceof DOMElement) {
			if ($node->hasAttribute('id')) {
				$id = $node->getAttribute('id');
			} else if (isset(self::$extensions[$node->nodeName])) {
				$ext = new self::$extensions[$node->nodeName]();
				$id = $ext->getID($node);
			} else {
				$id = false;
			}

			if ($id)
				$result[$id] = $node;
			foreach ($node->childNodes as $child) {
				$result = array_merge($result, $this->getIDs($child));
			}
		}
		return $result;
	}

	/*
		Method: inheritDesc
		Create a form description by inheriting another description.

		Parameters:
		$source - The node to inherit
		$desc		- The node that describes the modifications to the source node.

		Returns:
		A DOMNode that contains the merged form description.
	*/
	protected function inheritDesc(DOMNode $source, DOMNode $desc) {
		// Copy the node
		$source = $source->cloneNode(true);
		$newDesc = $desc->cloneNode(true);

		$desc->parentNode->replaceChild($source, $desc);

		// Find all ids within the source node
		$ids = $this->getIDs($source);

		// Work through the modifying nodes and modify the source appropriately.
		$nodes = array();
		foreach ($newDesc->childNodes as $node) {
			$nodes[]= $node;
		}
		foreach ($nodes as $node) {
			if ($node instanceof DOMElement) {
				if ($node->hasAttribute('id')) {
					$id = $node->getAttribute('id');
				} else if (isset(self::$extensions[$node->nodeName])) {
					$ext = new self::$extensions[$node->nodeName]();
					$id = $ext->getID($node);
				} else {
					$id = false;
				}

				if ($node->hasAttribute('remove') && !$id)
					throw new ParseException('remove attribute given without id.');

				$afterNode = null;

				if ($id && isset($ids[$id])) {
					// Node editing
					if ($node->hasAttribute('remove')) {
						// Remove the node
						$ids[$id]->parentNode->removeChild($ids[$id]);
					} else if ($node->hasAttribute('after')) {
						// Replace and move the node
						$after = $node->getAttribute('after');
						// Replace if other attribs besides the id & after were given, otherwise just
						// move the original node
						if ($node->attributes->length > 2) { // Replace
							$replaceNode = $node;
							$node->removeAttribute('after');
						} else { // Keep original
							$replaceNode = $ids[$id];
						}
						if ($after == '') {
							$ids[$id]->parentNode->removeChild($ids[$id]);
							$ids[$id] = $source->insertBefore($replaceNode, $source->firstChild);
						} else if (!isset($ids[$after])) {
							throw new ParseException('ID specified in after ('.$after.') does not exist');
						} else {
							$ids[$id]->parentNode->removeChild($ids[$id]);
							$ids[$id] = $ids[$after]->parentNode->insertBefore($replaceNode, $ids[$after]->nextSibling);
						}
					} else {
						// Simple replace
						$ids[$id] = $ids[$id]->parentNode->replaceChild($node, $ids[$id]);
					}
				} else {
					// New node
					if ($node->hasAttribute('after')) {
						if (isset($ids[$node->getAttribute('after')]))
							$afterNode = $ids[$node->getAttribute('after')];
						else
							throw new ParseException('after reference node '.$node->getAttribute('after').' not found');
					}
					if ($afterNode)
						$afterNode = $afterNode->parentNode->insertBefore($node, $afterNode->nextSibling);
					else
						$afterNode = $source->insertBefore($node, $source->firstChild);
				}
			}
		}

		return $source;
	}


	protected function parseConditions(HTMLNode $node) {
		if ($node instanceof HTMLTag) {
			if (isset($node->conditionString)) {
				$p = new ExprParser($node->conditionString, $this);
				$node->setCondition($p->parse());
				unset($node->conditionString);
			}
			foreach ($node->getChildren() as $child)
				$this->parseConditions($child);
		}
	}

	/*
		Method: getInputs
		Retrieves a list of all <HTMLInput> nodes under a given node.

		Parameters:
		$node - The node to gather inputs from.

		Returns:
		A map from input names to their nodes.

		Note:
		This function also fills the <$submits> array.
	*/
	protected function getInputs(HTMLNode $node) {
		$result = array();
		if ($node instanceof HTMLTag) {
			foreach ($node->getChildren() as $child)
				$result = array_merge($result, $this->getInputs($child));
		}
		if ($node instanceof HTMLInput) {
			$result[$node->getName()] = $node;
		}
		if ($node instanceof HTMLSubmit) {
			$this->submits[$node->getName()]= $node;
		}
		return $result;
	}

	/*
		Method: processErrorMsg
		Replaces <ErrorPlaceholders> with actual HTML nodes.
		This function keeps a list of input tags that have not been assigned an error message
		position. As soon as a placeholder is encountered, the error messages
		for the open inputs are placed at that point and the list is cleared.

		Parameters:
		$node - The node to process
	*/
	protected function processErrorMsg(HTMLNode $node) {
		if ($node instanceof HTMLTag) {
			$children = $node->getChildren();
			if ($this->templateParser->getErrorOrder($this->formID))
				$children = array_reverse($children, true);
			foreach ($children as $idx => $child) {
				if ($child instanceof ErrorPlaceholder) {
					// Error message tags need special processing

					$errTags = array();
					// For each open input:
					// Find all the error messages that can be assigned, and the error flags for when
					// they should be shown
					foreach ($this->openInputs as $input) {
						foreach ($input->getErrorMsgs() as $key => $msg) {
							$this->errorFlags[]= $key;
							$tag = array('msg' => $msg);

							// Apply template
							$tags = $this->templateParser->applyTemplate(
								$this->formID,
								'errormsg',
								$this->selectString,
								$tag,
								true            // require single root?
							);

							/*
							// Surround nodes with conditionals
							$tag = new OnErrorShowTag($key);
							$tag->addChildren($tags);
							$errTags[]= $tag;
							*/
							$tag = reset($tags);
							$style = $tag->getAttributes();
							if (isset($style['style']))
								$style = $style['style'].';';
							else
								$style = '';
							$tag->addAttributes(array('id' => '_err_'.$key, 'style' => $style.'{if !$formerrors.'.$key.'}display:none{/if}'));
							$errTags[]= $tag;
						}
					}

					if (count($errTags)==1)
						$tag = reset($errTags);
					else {
						$tag = new HTMLTag('');
						$tag->addChildren($errTags);
					}

					// Replace the placeholder with actual tags
					$node->replaceChild($idx, $tag);
					$this->openInputs = array();  // Done, clear array
				} else
					$this->processErrorMsg($child);
			}
		}
		if ($node instanceof HTMLInput)
			$this->openInputs[]= $node;
	}

	/*
		Method: parseHTML
		Parses a DOMNode into a <HTMLNode>.
		This implementation does not call its parent's parseHTML.

		Handled by this implementation:
		- *if* attribute
		- *ondisable* attribute
		- *errormsg* tags
		- Extension tags
		- templating of normal tags
	*/
	public function parseHTML(DOMNode $node) {
		if ($node instanceof DOMText) {
			// Plain text
			$tag = new HTMLText($node->data);
		} else if ($node instanceof DOMElement) {
			// DOMElement
			$oldSelect = $this->selectString;
			$this->selectString.= ' '.$node->nodeName;

			// Common processing: check for a conditional, store it separately and remove
			if ($node->hasAttribute('if')) {
				$conditional = $node->getAttribute('if');
				$node->removeAttribute('if');
			} else
				$conditional = null;

			if ($node->hasAttribute('ondisable')) {
				if (!$conditional)
					throw new ParseException('\'ondisable\' specified without \'if\' in tag '.$node->nodeName);
				$onDisable = $node->getAttribute('ondisable');
				$node->removeAttribute('ondisable');
				if ($onDisable!='hide' && $onDisable!='disable')
					throw new ParseException('Invalid value specified for ondisable: \''.$onDisable.'\' in tag '.$node->nodeName);
			} else
				$onDisable = 'hide';

			if ($node->nodeName == 'errormsg') {
				$tag = new ErrorPlaceholder();
			} else if (isset(self::$extensions[$node->nodeName])) {
				// Extension tag
				$ext = new self::$extensions[$node->nodeName]();
				$ext->parse($node, $this);
				$tplNodes = $ext->getHTMLNodes();

				if ($ext->getTemplateTagnames()) {
					$tags = $this->templateParser->applyTemplate(
						$this->formID,
						$node->nodeName,
						$this->selectString,
						$tplNodes,
						(bool)$conditional // require single root?
					);
					if (count($tags)==1)
						$tag = reset($tags);
					else {
						$tag = new HTMLTag('');
						$tag->addChildren($tags);
					}
				} else {
					$tag = $tplNodes;
				}
			} else {
				if ($this->templateParser->hasTemplate($this->formID, $node->nodeName)) {
					$tag = new HTMLTag('');
					$tag->addChildren($this->parseHTMLarray($node->childNodes));
					// Templated node
					$tags = $this->templateParser->applyTemplate(
						$this->formID, $node->nodeName, $this->selectString, array('content' => $tag),
						$node->hasAttributes());
					if (count($tags)==1)
						$tag = reset($tags);
					else {
						$tag = new HTMLTag('');
						$tag->addChildren($tags);
					}
				} else {
					$tag = new HTMLTag($node->nodeName);
					$tag->addChildren($this->parseHTMLarray($node->childNodes));
				}
				if ($node->hasAttributes())
					$tag->addAttributes($node->attributes); // Add remaining attrs
			}

			if ($conditional) {
				$tag->conditionString = $conditional;
				if ($onDisable=='disable')
					$tag->disableMethod = HTMLTag::Disable;
			}

			$this->selectString = $oldSelect;
		}
		return $tag;
	}

	// Group: Parsing support
	// Functions exposed for the convenience of extension tags

	/*
		Method: ensureUnused
		Throws an exception if the given id is already used.

		Parameters:
		$id - The id to ensure uniqueness for
	*/
	public function ensureUnused($id) {
		if (isset($this->usedIDs[$id]))
			throw new ParseException('ID '.$id.' already used.');
	}

	/*
		Method: addID
		Adds an id to the list of used ids

		Parameters:
		$id - The id to add
	*/
	public function addID($id) {
		$this->usedIDs[$id] = true;
	}

	/*
		Method: getProperty
		Returns the <Property> for a given name.

		Paramters:
		$id - The name of the property to retrieve.
	*/
	public function getProperty($id) {
		return $this->obj->getProperty($id);
	}

	/*
		Method: linkInputToProperty
		Indicates that a given property is manipulated by a given input.

		Parameters:
		$inputID    - The name of the input
		$propertyID - The name of the property
	*/
	public function linkInputToProperty($inputID, $propertyID) {
		// I guess we can change this function to just use one parameter, and rename it to something like
		// markPropertyAsUsed
		assert($inputID == $propertyID);
		$this->obj->getProperty($propertyID);
		$this->inputLinks[$propertyID] = $inputID;
	}

	/*
		Method: applyTemplate
		Applies a template.
		This is a wrapper around <TemplateParser::applyTemplate>, filling in a few values that extension tags
		do not know. Otherwise it functions the same.

		Parameters:
		$nodename   - The tag name within the form template to use
		$tplNodes   - An array, with the keys being the names of placeholder tags and their values a
		              single tag that will replace the named placeholder tag.
		$singleRoot - Indicates that the result should have a single root. This is required for example
		              when the placeholder tag had attributes.

		Returns:
		See <TemplateParser::applyTemplate>.
	*/
	public function applyTemplate($nodeName, $tplNodes, $singleRoot) {
		return $this->templateParser->applyTemplate($this->formID, $nodeName, $this->selectString, $tplNodes, $singleRoot);
	}

	public function hasTemplate($nodeName) {
		return $this->templateParser->hasTemplate($this->formID, $nodeName);
	}

	public function getFormName() { return $this->formName; }

	/*
		Method: getInput
		Retrieves a named input node.

		Parameters:
		$id - The name of the input to retrieve

		Returns:
		A <HTMLInput>.
	*/
	public function getInput($id) {
		if (isset($this->idToInput[$id]))
			return $this->idToInput[$id];
		else
			throw new Exception('Unknown ID: '.$id);
	}

	// Return an array of all input ids
	public function getInputList() {
		return array_keys($this->idToInput);
	}

}

?>