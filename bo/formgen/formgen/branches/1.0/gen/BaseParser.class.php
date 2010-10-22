<?php

require_once('HTML.class.php');
require_once('ParseException.class.php');
require_once('BaseParser.class.php');

function nodeName($xml) { return dom_import_simplexml($xml)->nodeName; }

/*
	Class: BaseParser
	Base class for the <DescParser> and <TemplateParser> xml parsers.

	This class provides some functionality that is required in both descendants, mainly the ability
	to parse DOM elements into <HTMLNodes>.
*/
class BaseParser {
	// Property: $xml
	// Contains the DOMDocument describing the xml to parse
	protected $xml = null;

	/*
		Constructor: __construct
		Creates a BaseParser and performs initial loading of an xml file

		Parameters:
		$filename - The filename of the xml file to load.

		The XML file is prefixed with an xml header (<?xml ... ?>) and a doctype that
		describes all HTML entities. This allows those html entities to be used within
		the xml files.
	*/
	public function __construct($filename) {
		$xml = file_get_contents($filename);
		$xml = '<?xml version="1.0" encoding="ISO-8859-1" ?><!DOCTYPE html SYSTEM "file:///'.str_replace('\\', '/', dirname(__FILE__)).'/xhtml.ent">'.$xml;

		$this->xml = new DOMDocument();
		$this->xml->resolveExternals = true;
		$this->xml->substituteEntities = true;
		if (!$this->xml->loadXML($xml))
			throw new ParseException('Failed reading XML file ('.$filename.')');
	}

	/*
		Method: parseHTMLarray
		Parses a DOMNodeList into an array of <HTMLNodes>.

		Parameters:
		$list - The DOMNodeList to parse.
		$trim - Determines whether the list is trimmed. Trimming will remove the
		  first and last node of the list if these nodes are text nodes that only
		  contain spaces.

		Returns:
		An array of HTMLNodes.
	*/
	public function parseHTMLarray(DOMNodeList $list, $trim = false) {
		$result = array();
		foreach ($list as $node) {
			if ($node instanceof DOMComment)
				continue;
			if ($trim && $node instanceof DOMText && trim($node->data)=='' &&
			    ($node->previousSibling === null || $node->nextSibling === null))
				continue;
			$result[]= $this->parseHTML($node);
		}
		return $result;
	}

	/*
		Method: parseHTML
		Parses a DOMNode into a <HTMLNode>.

		This base version converts DOMText nodes into <HTMLText> nodes, and DOMElements
		into <HTMLTags>. The node attribute 'if' is handled as well.

		Parameters:
		$node - The DOMNode to parse.

		Returns:
		A HTMLNode.
	*/
	public function parseHTML(DOMNode $node) {
		if ($node instanceof DOMText) {
			// Plain text
			$tag = new HTMLText($node->data);
		} else {
			// DOMElement
			$tag = new HTMLTag($node->nodeName);
			$tag->addChildren($this->parseHTMLarray($node->childNodes));

			// Common processing: check for a conditional, and add any remaining attributes
			if ($node->hasAttribute('if')) {
				$tag->conditionString = $node->getAttribute('if');
				$node->removeAttribute('if');
			}
			$tag->addAttributes($node->attributes);
		}
		return $tag;
	}

	/*
		Method: getElementsByName
		Retrieves a list of all child nodes with a given tagname, non-recursive.

		Parameters:
		$node    - The node to search through
		$tagname - The node names to search for

		Returns:
		An array with the found nodes.
	*/
	public function getElementsByName(DOMElement $node, $tagname) {
		$result = array();
		foreach ($node->childNodes as $child) {
			if ($child->nodeName == $tagname)
				$result[]= $child;
		}
		return $result;
	}
}

?>