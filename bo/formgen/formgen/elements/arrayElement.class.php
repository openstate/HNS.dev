<?php

interface StateDuplicate {
	public function getState();
	public function setState($state);
}

class arrayElement extends InputElement implements Validator {
	protected $content;
	protected $childInputs = array();
	protected $checks = array();
	protected $checksFailed = array();
	protected $validators = array();

	protected $data = array();
	protected $states = array();
	protected $stateDups = array();

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		$result = '';
		$subContext = $context->getCopy();
		$origErrorPos = $context->errorPositions;
		$name = $subContext->makeName($this->name);
		$subContext->namePostfix = ']';
		$firstErr = $this->findFirstErrorLoc($this->content);
		if ($firstErr)
			$subContext->closestError = $firstErr;

		foreach ($this->data as $key => $value) {
			$subContext->errorOffset = 0;
			$subContext->namePrefix  = $name.'['.$key.'][';
			$subContext->errorPositions = array();
			$subContext->idPrefix = $context->idPrefix.$this->name.'_'.$key;

			foreach ($this->childInputs as $i)
				$i->setFromData($value, true);

			foreach ($this->checks as $c)
				$c->failed = false;
			if (isset($this->checksFailed[$key])) {
				foreach ($this->checksFailed[$key] as $k => $failed)
					$this->checks[$k]->failed = true;
			}

			$html = $this->content->getHtml($subContext);
			$subContext->resolveCheckLocs($this->checks, $html);
			$result .= $html;

			$this->states[$key] = array();
			foreach ($this->stateDups as $k => $elem)
				$this->states[$key][$k] = $elem->getState();
		}
		$context->errorPositions = $origErrorPos;
		$context->errorOffset += strlen($result);
		return $result;
	}

	protected function getSubInputs(HtmlNode $node) {
		if ($node instanceof InputElement)
			return array($node);
		else {
			$result = array();
			foreach ($node->children as $child)
				$result = array_merge($result, $this->getSubInputs($child));
			return $result;
		}
	}

	protected function getStateDuplicates(HtmlNode $node) {
		$result = array();
		foreach ($node->children as $child)
			$result = array_merge($result, $this->getStateDuplicates($child));

		$result[] = $node;

		return $result;
	}

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		$context->checks = array();
		$context->validators = array();
		$context->inputElement = null;
		parent::parse($node, $parser, $context);
		$this->content = new HtmlTag('');
		$this->content->addChildren($parser->parseNodes($node->childNodes, $context));
		$this->content->parent = $this;
		$this->checks = $context->checks;
		$this->validators = $context->validators;

		$context->checks = array();
		$context->validators = array();

		$this->childInputs = $this->getSubInputs($this->content);
		$this->stateDups = $this->getStateDuplicates($this->content);
		foreach ($this->checks as $c)
			$this->stateDups[] = $c;

		while ($node->childNodes->length > 0)
			$node->removeChild($node->childNodes->item(0));
	}

	public function isGiven()  { throw new Exception('array::isGiven called');  }
	public function getValue() { throw new Exception('array::getValue called'); }

	public function getCondition() {
		$this->required = true;
		return parent::getCondition();
	}

	public function setFromData($data) {
		if (isset($data[$this->name]))
			$this->data = $data[$this->name];
	}

	public function getAllValues() {
		$result = array();
		foreach ($this->data as $key => $value) {
			$result[$key] = array();
			foreach ($this->childInputs as $i) {
				$i->setFromData($value, true);
				$cond = $i->getCondition();
				if (!$cond || $cond->evaluate())
					$result[$key] = array_merge($result[$key], $i->getAllValues());
			}
		}
		return array($this->name => $result);
	}

	public function isValid($callbacks) {
		$result = true;

		foreach ($this->data as $key => $value) {
			foreach ($this->childInputs as $i) {
				$i->setFromData($value, true);
				$i->marked = false;
			}

			foreach ($this->checks as $ckey => $check) {
				$cond = $check->getCondition();
				if (!$cond || $cond->evaluate()) {
					if ($check->targetsUnmarked() && !$check->valid($callbacks)) {
						$check->markTargets(); // Mark all this check's targets as having failed a check, so any later checks are not executed.
						$this->checksFailed[$key][$ckey] = true;
						$result = false;
					}
				}
			}
		}

		foreach ($this->validators as $v) {
			if (!$v->isValid($callbacks))
				$result = false;
		}

		return $result;
	}

	public function getAllChecks() {
		return array_merge($this->checks, parent::getAllChecks());
	}

	public function getConditions() {
		$result = array();
		foreach ($this->data as $key => $value) {

			foreach ($this->stateDups as $k => $elem)
				$elem->setState($this->states[$key][$k]);

			$result = array_merge($result, $this->content->getConditions());

			foreach ($this->checks as $c) {
				$condition = $c->getCondition();
				if ($condition) {
					$targets = $this->getTargetNames($condition->getTargets());
					$condition = new BinaryExpr('&&', $condition, new UnaryExpr('!', $c->getExpr()));
				} else {
					$condition = new UnaryExpr('!', $c->getExpr());
					$targets = array();
				}

				$result[] = array(
					'id'           => $this->state['idPrefix'].$c->getLocation()->uniqueName,
					'condition'    => $condition->getJS(0),
					'targets'      => $targets,
					'checkTargets' => $this->getTargetNames($c->getTargets())
				);
			}
		}

		return $result;
	}
}

?>