<?php

require_once('Element.class.php');

interface Validator {
	public function isValid($callbacks);
}

class Form extends Element implements Validator {
	protected $inputs = array(), $checks = array(), $validators = array(), $namedErrorLocs = array();

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

	public function getInputs() {
		return $this->getSubInputs($this);
	}

	public function getChecks() {
		return $this->checks;
	}

	public function getNamedErrorLocations() {
		return $this->namedErrorLocs;
	}

	public function addNamedErrorLocations(array $locs) {
		$this->namedErrorLocs = array_merge($this->namedErrorLocs, $locs);
	}

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);
		$this->children = $parser->parseNodes($node->childNodes, $context);
		$this->checks = $context->checks;
		$this->validators = $context->validators;
	}

	public function isValid($callbacks) {
		$result = true;
		foreach ($this->checks as $check) {
			$cond = $check->getCondition();
			if (!$cond || $cond->evaluate()) {
				if ($check->targetsUnmarked() && !$check->valid($callbacks)) {
					$check->markTargets(); // Mark all this check's targets as having failed a check, so any later checks are not executed.
					$check->failed = true;
					$result = false;
				}
			}
		}

		foreach ($this->validators as $v)
			if (!$v->isValid($callbacks))
				$result = false;

		return $result;
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);

		$subContext = $context->getCopy();
		$origErrorPos = $context->errorPositions;

		$subContext->errorOffset = 0;
		$subContext->errorPositions = array();

		$result = '';
		$errorLoc = $this->findFirstErrorLoc($this);
		if ($errorLoc)
			$subContext->closestError = $errorLoc;
		foreach ($this->children as $c)
			$result .= $c->getHtml($subContext);

		$subContext->resolveCheckLocs($this->checks, $result);
		$context->errorPositions = $origErrorPos;
		$context->errorOffset += strlen($result);
		return $result;
	}

	public function getAllChecks() {
		return array_merge($this->checks, parent::getAllChecks());
	}

	public function getJSParts() {
		$checks = array();
		$i = 0;
		$targets = array();
		foreach ($this->checks as $check) {
			$cEx = $check->getExpr();

			$cond = $check->getCondition();
			if ($cond) {
				$cEx = new BinaryExpr('&&', $cond, new UnaryExpr('!', $cEx));
			}

			$checks[$i] = $cEx->getJS();

			foreach ($cEx->getTargets() as $t) {
				$targets[$t->getName]['checks'][] = $i;
			}

			$i++;
		}

		foreach ($this->getConditions() as $cond) {
			foreach ($cond->getTargets() as $t)
				$targets[$t->getName]['dom'][] = $cond->getAssociatedElements();
		}
	}

	public function getConditions() {
		$result = parent::getConditions();
		foreach ($this->checks as $c) {
			if ($c->getLocation()) {
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