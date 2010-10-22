<?php

require_once('ElemTemplate.class.php');

abstract class Element extends HtmlNode {
	protected $attributes = array();
	protected $tplVariation = false;

	protected function getAttributeHtml() {
		$result = '';
		foreach ($this->attributes as $name => $val)
			$result .= ' '.$name.'="'.htmlentities($val, ENT_COMPAT, 'UTF-8').'"';

		return $result;
	}

	protected function applyTemplate($file, $variables, HtmlContext $context) {
		$fileParts = pathinfo($file);
		if ($this->tplVariation) {
			$fileName = $fileParts['filename'].'.'.$this->tplVariation.'.'.$fileParts['extension'];
		} else
			$fileName = $fileParts['filename'].'.'.$fileParts['extension'];

		$file = dirname(__FILE__).'/../../templates/'.$fileName;
		if (!file_exists($file))
			$file = dirname(__FILE__).'/../templates/'.$fileName;

		$tpl = new ElemTemplate($file);
		$positions = $tpl->findErrorPositions($context->errorOffset);
		$vars = array();
		$varPos = $tpl->findVarPositions($context->errorOffset);
		$context->errorPositions = array_merge($context->errorPositions, array_values($positions['locations']));
		$origContext = $context;
		$context = $context->getCopy();
		$errorOffset = $context->errorOffset;

		$variables['attributes'] = $this->getAttributeHtml();

		$currErr = reset($positions['locations']);
		$errDelta = 0;
		foreach ($varPos as $pos) {
			if (!isset($variables[$pos['name']]) && substr($pos['name'], -9) == '.required') {
				$var = substr($pos['name'], 0, -9);
				if (isset($variables[$var]) && $variables[$var] instanceof HtmlNode) {
					$variables[$pos['name']] = $variables[$var]->isAllRequired() ? 'true' : 'false';
				}
			}

			if (!isset($variables[$pos['name']]))
				throw new ParseException('Unknown reference in template '.$file.': '.$pos['name']);

			while ($currErr && $currErr->start < $pos['start']) {
				$currErr->offset($errDelta);
				$currErr = next($positions['locations']);
			}

			$var = $variables[$pos['name']];
			if ($var instanceof HtmlNode) {
				if (isset($positions['links'][$pos['name']]))
					$context->closestError = $positions['links'][$pos['name']];
				$context->errorOffset = $pos['start'] + $errDelta;
				$vars[$pos['name']] = $var->getHtml($context);
			} else
				$vars[$pos['name']] = $var;

			$errDelta += strlen($vars[$pos['name']]) - $pos['length'];
		}

		if ($currErr) {
			do {
				$currErr->offset($errDelta);
			} while ($currErr = next($positions['locations']));
		}

		$result = $tpl->apply($vars);
		$origContext->errorOffset += strlen($result);
		$origContext->closestError = $context->closestError;
		return $result;
	}

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		if ($node->hasAttribute('tpl'))
			$this->tplVariation = $node->getAttribute('tpl');
		foreach ($node->attributes as $attr) {
			if ($attr->namespaceURI == FormParser::nsRawAttr) {
				$this->attributes[$attr->localName] = $attr->value;
			}
		}
	}
}

?>