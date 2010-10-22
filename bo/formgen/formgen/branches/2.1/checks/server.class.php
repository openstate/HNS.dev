<?php

class server extends Check {
	protected $validOptions = array('callback' => true);
	protected $callback;
	protected $defaultTarget = 'target';
	protected $targets = array('target' => null);

	public function __construct($errorMsg, InputElement $target = null) {
		parent::__construct($errorMsg, $target);
		if (!$this->targets['target'])
			unset($this->targets['target']);
	}

	public function getExpr() {
		return new GenericExpr(
			array($this, 'phpExpr'),
			array($this, 'jsExpr'),
			null,
			array($this, 'exGetTargets')
		);
	}

	public function setParam($name, $value, ExprParser $expr) {
		if ($name == 'target') {
			$e = $expr->parse();
			if (!$e instanceof InputExpr)
				throw new ParseException('Check target '.$value.' is not a valid input reference.');
			$this->targets[] = $e->getInput();
		} else
			parent::setParam($name, $value, $expr);
	}

	public function phpExpr($indent, $data) {
		throw Exception('server::phpExpr not yet implemented.');
	}

	public function jsExpr($indent, $data) {
		return 'true';
	}

	public function exGetTargets($data) {
		return array();
	}

	public function valid($callbacks) {
		if (!isset($callbacks[$this->callback]))
			throw new Exception('Unknown server check callback: '.$this->callback);
		// Grab values
		$values = array();
		foreach ($this->targets as $t) {
			if ($t) $values[$t->getFullName()] = $t->getValue();
		}
		return call_user_func($callbacks[$this->callback], $values);
	}
}

?>