<?php

class QuickElement extends Element {
	protected $attrs = array();
	protected $tags  = array();
	protected $contentName = false;

	protected $vars = array();

	protected $name;

	public function __construct($source) {
		parent::__construct();
		$this->name = pathinfo($source, PATHINFO_FILENAME);
		$xmlSrc = file_get_contents($source);
		$xmlSrc = '<?xml version="1.0" encoding="ISO-8859-1" ?>'.
			'<!DOCTYPE html SYSTEM "file:///'.str_replace('+', '%20', urlencode(str_replace('\\', '/', dirname(__FILE__)))).'/xhtml.ent">'.$xmlSrc;

		$xml = new DOMDocument();
		$xml->resolveExternals = true;
		$xml->substituteEntities = true;
		if (!$xml->loadXML($xmlSrc))
			throw new ParseException('Failed reading XML file ('.$filename.')');

		for ($i = 0; $i < $xml->documentElement->childNodes->length; $i++) {
			$el = $xml->documentElement->childNodes->item($i);
			if (!($el instanceof DOMElement))
				continue;

			if (!$el->hasAttribute('name'))
				throw new ParseException('No name attribute specified for tag '.$el->tagName.' in '.$source);
			$name = $el->getAttribute('name');
			$this->vars[$name] = '';
			if ($el->tagName == 'attr') {
				$this->attrs[$name] = $name;
			} else if ($el->tagName == 'tag') {
				$this->tags[$name] = $name;
			} else if ($el->tagName == 'content') {
				$this->contentName = $name;
			} else
				throw new ParseException('Unknown tag '.$el->tagName.' in '.$source);

			if ($this->contentName !== false && count($this->tags) > 0)
				throw new ParseException('Cannot combine content and tags in '.$source);
		}
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		return $this->applyTemplate($this->name.'.html', $this->vars, $context);
	}

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);
		foreach ($this->attrs as $attr) {
			if (!$node->hasAttribute($attr))
				throw new ParseException('Missing attribute '.$attr.' in tag '.$this->name);
			$this->vars[$attr] = $node->getAttribute($attr);
		}

		if ($this->contentName !== false) {
			$tag = new HtmlTag('');
			$tag->parent = $this;
			$tag->addChildren($parser->parseNodes($node->childNodes, $context));
			$this->vars[$this->contentName] = $tag;
			$this->children[] = $tag;
		} else {
			foreach ($this->tags as $tagName) {
				$tags = $node->getElementsByTagName($tagName);
				if ($tags->length == 0)
					throw new ParseException('Missing child tag '.$tagName.' in tag '.$this->name);

				$tag = new HtmlTag('');
				$tag->parent = $this;
				$tag->addChildren($parser->parseNodes($tags->item(0)->childNodes, $context));
				$this->vars[$tagName] = $tag;
				$this->children[] = $tag;
			}
		}
	}
}

?>