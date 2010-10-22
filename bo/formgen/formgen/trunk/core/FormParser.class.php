<?php

require_once('Html.class.php');
require_once('Element.class.php');
require_once('InputElement.class.php');
require_once('QuickElement.class.php');
require_once('Form.class.php');
require_once('Check.class.php');
require_once('expressions/ExprParser.class.php');

class ParseException extends Exception {}

class ParseContext {
	public $inputElement = null;  // Used to autolink checks
	public $checks     = array();
	public $validators = array();
}

class FormParser {
	const nsElement = 'urn:fg:elem';
	const nsCheck   = 'urn:fg:check';
	const nsParam   = 'urn:fg:param';
	const nsSpecial = 'urn:fg:special';
	const nsRawAttr = 'urn:fg:rawAttrib';

	private $nameSpaces = array(
		'e' => self::nsElement,
		'c' => self::nsCheck,
		'f' => self::nsSpecial,
		'p' => self::nsParam,
		'r' => self::nsRawAttr
	);

	private $nsCallbacks = array();

	private $elems  = array();
	private $qElems = array();
	private $checks = array();

	private $defaultErrors = array();

	private $inputList, $namedErrorLocs;

	private $errorNodes = array();
	private $useNext;
	private $seenInput = false, $seenError = false;

	private $fileName;

	public function __construct() {
		$this->nsCallbacks = array(
			self::nsElement => array($this, 'handleElement'),
			self::nsCheck   => array($this, 'handleCheck')
		);

		foreach (array_merge(glob(dirname(__FILE__).'/../elements/*.php'), glob(dirname(__FILE__).'/../../elements/*.php')) as $file) {
			require_once($file);
			$file = basename($file);
			$tag = $class = substr($file, 0, strpos($file, '.'));
			if (substr($tag, -7) == 'Element')
				$tag = substr($tag, 0, -7);
			$this->elems[$tag] = $class;
		}

		foreach (array_merge(glob(dirname(__FILE__).'/../elements/*.xml'), glob(dirname(__FILE__).'/../../elements/*.xml')) as $file) {
			$tag = basename($file);
			$tag = substr($tag, 0, strpos($tag, '.'));
			$this->qElems[$tag] = $file;
		}

		foreach (array_merge(glob(dirname(__FILE__).'/../checks/*.php'), glob(dirname(__FILE__).'/../../checks/*.php')) as $file) {
			require_once($file);
			$file = basename($file);
			$tag = substr($file, 0, strpos($file, '.'));
			$this->checks[$tag] = $tag;
		}

		$this->defaultErrors = require(dirname(__FILE__).'/../config/errors.inc.php');
		if (file_exists(dirname(__FILE__).'/../../config/errors.inc.php')) {
			$this->defaultErrors = array_merge(
				$this->defaultErrors,
				require(dirname(__FILE__).'/../../config/errors.inc.php')
			);
		}
	}

	public function loadFile($filename) {
		$this->fileName = $filename;
		$this->loadString(file_get_contents($filename));
	}

	public function loadString($xml) {
		$nameSpaces = '';
		foreach ($this->nameSpaces as $space => $uri) {
			$nameSpaces .= ' xmlns:'.$space.'="'.$uri.'"';
		}

		$xml = '<?xml version="1.0" encoding="ISO-8859-1" ?>'.
			'<!DOCTYPE html SYSTEM "file:///'.str_replace('+', '%20', urlencode(str_replace('\\', '/', dirname(__FILE__)))).'/xhtml.ent">'.
			preg_replace('/^\s*(<[^\s>]+)/', '\1'.$nameSpaces, $xml);

		$this->xml = new DOMDocument();
		$this->xml->resolveExternals = true;
		$this->xml->substituteEntities = true;
		if (!$this->xml->loadXML($xml))
			throw new ParseException('Failed reading XML file ('.$filename.')');
	}

	public function parse() {
		$this->inputList = array();
		$this->namedErrorLocs = array();

		$nodes = $this->xml->getElementsByTagName('error');
		$next = null;
		for ($i = $nodes->length-1; $i >= 0; $i--) {
			$node = $nodes->item($i);
			$loc = new ErrorMsg(0, 0, $node->getAttribute('name'));
			if ($node->hasAttribute('tpl'))
				$loc->setTplVariation($node->getAttribute('tpl'));
			if ($node->hasAttribute('name')) {
				if (isset($this->namedErrorLocs[$loc->name]))
					throw new ParseException('Duplicate name for named error location: '.$loc->name);
				$this->namedErrorLocs[$loc->name] = $loc;
			}
			$next = $this->errorNodes[$i] = new ErrorNode($loc, $next);
			$node->setAttribute('id', $i);
		}
		$this->useNext = false;
		$this->seenError = false;
		$this->seenInput = false;

		$result = new Form();
		$result->parse($this->xml->documentElement, $this, new ParseContext());
		$result->addNamedErrorLocations($this->namedErrorLocs);

		foreach ($this->errorNodes as $n)
			$n->setUseNext($this->useNext);
		return $result;
	}

	/*protected*/public function parseNode(DOMNode $node, ParseContext $context) {
		if ($node instanceof DOMText) {
			// Text node, find text in $node->data, which is utf-8 encoded.
			return new HtmlText($node->data);
		} else if ($node instanceof DOMProcessingInstruction) {
			// Processing instruction, target is in $node->target, data is in $node->data.
			// Processing instructions are of the form <?target data.... ? >  (<?target? > is valid as well)
			// Character entities are not parsed within the data.

			if ($node->target == 'literal')
				return new HtmlText($node->data, false);
			//return new HtmlText('{Processing['.$node->target.']: '.$node->data.'}');
		} else if ($node instanceof DOMElement) {
			// Element / tag.
			// Full tagname (what is literally in the xml source) is in $node->tagname
			// If this tag is namespaced:
			// - $node->namespaceURI specifies the ns URI
			// - $node->localName    specifies the part after the ns prefix
			// - $node->prefix       specifies the literal prefix text used

			// $node->attributes is a DOMNamedNodeMap with the node's attributes.
			// Namespaced node elements will have the above-mentioned namespace attributes available
			// See also the getAttributeNS and friends.

			// $node->childNodes is a DOMNodeList with the node's children.

			if ($node->namespaceURI !== null && isset($this->nsCallbacks[$node->namespaceURI])) {
				// Namespaced node, and a callback is available: use the callback's handling.
				return call_user_func($this->nsCallbacks[$node->namespaceURI], $node, $context);
			} else if ($node->tagName == 'error') {
				if (!$this->seenError && !$this->seenInput)
					$this->useNext = false;
				$this->seenError = true;
				return $this->errorNodes[$node->getAttribute('id')];
			} else {
				// Treat as a regular node.

				$n = new HtmlTag($node->tagName);

				if ($node->hasAttribute('if')) {
					$ep = new ExprParser($node->getAttribute('if'), array(array($this, 'getIdExpression'), $context));
					$method = null;
					if ($node->hasAttribute('ifMethod')) {
						$method = $node->getAttribute('ifMethod');
						$node->removeAttribute('ifMethod');
					}
					$n->setCondition($ep->parse(), $method);
					$node->removeAttribute('if');
				} // TODO: This code is somewhat duplicated in handleElement, refactor this.

				$n->addAttributes($node->attributes);
				$n->addChildren($this->parseNodes($node->childNodes, $context));
				return $n;
			}
		} else if ($node instanceof DOMComment) {
			// Skip
		} else
			throw new ParseException('Unknown node: '.get_class($node));
	}

	/*protected*/public function parseNodes(DOMNodeList $nodes, ParseContext $context, $wrap = false) {
		$result = array();
		for ($i = 0; $i < $nodes->length; $i++) {
			$r = $this->parseNode($nodes->item($i), $context);
			if ($r !== null) $result[] = $r;
		}
		if ($wrap) {
			$tag = new HtmlTag('');
			$tag->addChildren($result);
			$result = $tag;
		}
		return $result;
	}

	protected function handleElement(DOMElement $node, ParseContext $context) {
		if ($node->localName == 'form') {
			if (!$node->hasAttribute('source'))
				throw new ParseException('No source file specified for subform');
			$file = dirname($this->fileName).'/'.$node->getAttribute('source');

			$parser = new FormParser();
			$parser->loadFile($file);
			$form = $parser->parse();

			$this->inputList = array_merge($this->inputList, $parser->inputList);
			//$context->checks = array_merge($context->checks, $form->getChecks());
			if ($form instanceof Validator)
				$context->validators[] = $form;
			return $form;
		} else if (isset($this->elems[$node->localName])) {
			$elem = new $this->elems[$node->localName]();
			if ($elem instanceof InputElement) {
				$origCtx = $context;
				$context = clone $origCtx;
				$context->checks = array();
				$context->inputElement = $elem;
			}

			if ($node->hasAttribute('if')) {
				$ep = new ExprParser($node->getAttribute('if'), array(array($this, 'getIdExpression'), $context));
				$condition = $ep->parse();
				$node->removeAttribute('if');
				$method = null;
				if ($node->hasAttribute('ifMethod')) {
					$method = $node->getAttribute('ifMethod');
					$node->removeAttribute('ifMethod');
				}
			} // TODO: This code is somewhat duplicated in parseNode, refactor this.
				else $condition = null;

			$elem->parse($node, $this, $context);

			if ($condition)
				$elem->setCondition($condition, $method);

			if ($elem instanceof InputElement) {
				if (!$this->seenError && !$this->seenInput)
					$this->useNext = true;
				$this->seenInput = true;
				if (isset($this->inputList[$elem->getName()]))
					throw new ParseException('Duplicate input id: '.$elem->getName());
				$this->inputList[$elem->getName()] = $elem;
				// Parse for any remaining checks
				foreach ($node->childNodes as $child) {
					if ($child instanceof DOMElement) {
						if ($child->namespaceURI == self::nsCheck)
							$this->handleCheck($child, $context);
						else
							throw new ParseException('Invalid tag within input element '.$node->tagName.': '.$child->tagName);
					}
				}

				$origCtx->checks = array_merge($origCtx->checks, $context->checks);
				$context = $origCtx;
			}

			if ($elem instanceof Validator)
				$context->validators[] = $elem;

			return $elem;
		} else if (isset($this->qElems[$node->localName])) {
			$elem = new QuickElement($this->qElems[$node->localName]);
			$elem->parse($node, $this, $context);
			return $elem;
		} else
			throw new ParseException('Unknown tag: '.$node->tagName);
	}

	public function getIdExpression($id, ParseContext $context) {
		$parts = explode('->', $id);
		$firstPart = array_shift($parts);
		if ($firstPart == 'this' && $context->inputElement)
			$currPart = $context->inputElement;
		else if (isset($this->inputList[$firstPart]))
			$currPart = $this->inputList[$firstPart];
		else
			throw new ParseException('Unknown id: '.$id);

		foreach ($parts as $p) {
			$inputs = $currPart->getChildValues();
			if (!isset($inputs[$p]))
				throw new ParseException('Unknown id: '.$id);
			$currPart = $inputs[$p];
		}
		return new InputExpr($currPart);
	}

	protected function handleCheck(DOMElement $node, ParseContext $context) {
		if (isset($this->checks[$node->localName])) {
			$errClass = $this->checks[$node->localName];
			if (!isset($this->defaultErrors[$errClass]))
				throw new ParseException('No default error message for check type '.$node->localName);
			if ($context->inputElement)
				$result = new $errClass($this->defaultErrors[$errClass], $context->inputElement);
			else
				$result = new $errClass($this->defaultErrors[$errClass]);

			foreach ($node->childNodes as $child) {
				if ($child instanceof DOMElement) {
					if ($child->tagName == 'msg')
						$result->setErrorMsg($this->parseNodes($child->childNodes, $context, true)->getHtml(new HtmlContext()));
					else if ($child->tagName == 'position') {
						if (!$child->hasAttribute('name'))
							throw new ParseException('No name attribute given for target error position');
						$result->setNamedPosition($child->getAttribute('name'));
					} else if ($child->namespaceURI == self::nsParam)
						$result->setParam($child->localName, $child->nodeValue, new ExprParser($child->nodeValue, array(array($this, 'getIdExpression'), $context)));
					else
						throw new ParseException('Invalid tag within check: '.$child->tagName);
				}
			}
		} else
			throw new ParseException('Unknown check: '.$node->tagName);
		$context->checks[] = $result;
		return null;
	}
}

?>