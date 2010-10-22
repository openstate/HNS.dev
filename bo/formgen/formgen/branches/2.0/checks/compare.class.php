<?php

class compare extends Check {
	protected $expression;
	protected $defaultTarget = 'target'; // Default target will be ignored
	protected $targets = array('target' => null);
	protected $locationTarget = null;

	public function __construct($errorMsg, InputElement $target = null) {
		parent::__construct($errorMsg, $target);
		if ($this->targets['target'])
			$this->locationTarget = $this->targets['target'];
		$this->targets = array();
	}

	public function setParam($name, $value, ExprParser $expr) {
		if ($name == 'target') {
			$e = $expr->parse();
			if (!$e instanceof InputExpr)
				throw new ParseException('Check target '.$value.' is not a valid input reference.');
			$this->locationTarget = $e->getInput();
		} else if ($name == 'expr') {
			$this->expression = $expr->parse();
			$this->targets = array_merge($this->targets, $this->expression->getTargets());
		} else
			throw new ParseException('Unknown option: '.$name);
	}

	public function getFirstTarget() {
		if ($this->locationTarget)
			return $this->locationTarget->getInputElement();
		else
			return parent::getFirstTarget();
	}

	public function getExpr() {
		return $this->expression;
	}

	public function valid($callbacks) {
		return $this->expression->evaluate();
	}
}

?>