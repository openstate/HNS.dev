<?php

class Tab extends Element {
	protected $title;
	protected $description = '';
	protected $content     = null;

	public function getHtml(HtmlContext $context) {
		return $this->applyTemplate('tab.html',
			array(
				'title'       => $this->title,
				'description' => $this->description,
				'content'     => $this->content
			),
			$context);
	}

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		if (!$node->hasAttribute('title') || !$node->getAttribute('title'))
			throw new ParseException('No title given for tab.');
		$this->title       = $node->getAttribute('title');
		$this->description = $node->hasAttribute('description') ? $node->getAttribute('description') : '';
		$this->content     = $parser->parseNodes($node->childNodes, $context, true);
	}
}

class tabs extends Element {
	protected $tabs = null;

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		return $this->applyTemplate('tabs.html',
			array('content' => $this->tabs), $context);
	}

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);

		foreach ($node->childNodes as $child) {
			if ($child instanceof DOMElement && $child->tagName == 'tab') {
				$tab = new Tab();
				$tab->parse($child, $parser, $context);
				$tabs[] = $tab;
			}
		}
		$this->tabs = new HtmlTag('');
		$this->tabs->addChildren($tabs);
	}
}

?>