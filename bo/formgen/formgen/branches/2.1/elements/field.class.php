<?php

class field extends Element {
	protected $label = null, $content = null;

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		if ($this->id)
			$this->attributes['id'] = $context->idPrefix.$this->id;

		return $this->applyTemplate('field.html',
			array('label' => $this->label, 'content' => $this->content),
			$context);
	}

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);
		$label = $content = array();
		foreach ($node->childNodes as $c) {
			if ($c instanceof DOMElement and $c->tagName == 'label')
				$label[] = $c;
			if ($c instanceof DOMElement and $c->tagName == 'content')
				$content[] = $c;
		}

		if (count($label) == 0)
			throw new ParseException('Label missing for field');
		if (count($label) > 1)
			throw new ParseException('Multiple labels given for field');
		$label = reset($label);

		if (count($content) == 0)
			throw new ParseException('Content missing for field');
		if (count($content) > 1)
			throw new ParseException('Multiple contents given for field');
		$content = reset($content);

		$this->label   = $parser->parseNodes($label->childNodes, $context, true);
		$this->content = $parser->parseNodes($content->childNodes, $context, true);

		$this->label->parent = $this;
		$this->content->parent = $this;
		$this->children = array($this->label, $this->content);
	}
}

?>