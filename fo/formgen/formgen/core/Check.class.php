<?php

require_once('expressions/BaseExpressions.class.php');

abstract class Check {
	protected $errorMsg;
	protected $validOptions = array();
	protected $defaultTarget = null;
	protected $targets = array();
	protected $namedPosition = false;
	protected $errorLocation = null;

	public    $failed = false;

	public function __construct($errorMsg, InputElement $target = null) {
		$this->errorMsg = $errorMsg;
		if ($target !== null) {
			if (!$this->defaultTarget)
				throw new ParseException('No default input support for '.get_class($this));
			$this->targets[$this->defaultTarget] = $target;
		}
	}

	public function setParam($name, $value, ExprParser $expr) {
		if (isset($this->validOptions[$name]))
			$this->$name = $value;
		else if (array_key_exists($name, $this->targets)) {
			$e = $expr->parse();
			if (!$e instanceof InputExpr)
				throw new ParseException('Check target '.$value.' is not a valid input reference.');
			$this->targets[$name] = $e->getInput();
		} else
			throw new ParseException('Unknown option: '.$name);
	}

	public function getErrorMsg()     { return $this->errorMsg; }
	public function setErrorMsg($msg) { $this->errorMsg = $msg; }

	public function getNamedPosition()     { return $this->namedPosition; }
	public function setNamedPosition($pos) { $this->namedPosition = $pos; }

	public function targetsUnmarked() {
		foreach ($this->targets as $t)
			if ($t->getInputElement()->marked)
				return false;
		return true;
	}

	public function markTargets() {
		foreach ($this->targets as $t)
			$t->getInputElement()->marked = true;
	}

	public function getCondition() {
		$ex = array();
		foreach ($this->targets as $tgt) {
			$c = $tgt->getInputElement()->getCondition();
			if ($c)
				$ex[] = $c;
		}
		if (count($ex) > 0)
			return new NaryExpr('&&', $ex);
		else
			return null;
	}

	public function getFirstTarget() {
		return reset($this->targets)->getInputElement();
	}

	public function getTargets() {
		$result = array();
		foreach ($this->targets as $key => $tgt)
			$result[$key] = $tgt->getInputElement();
		return $result;
	}

	abstract public function getExpr();
	abstract public function valid($callbacks);

	public function setLocation($loc) { $this->errorLocation = $loc; }
	public function getLocation() { return $this->errorLocation; }

	public function getState() {
		return array('location' => $this->errorLocation);
	}
	public function setState($state) {
		$this->errorLocation = $state['location'];
	}
}

?>