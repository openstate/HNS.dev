<?php

class regex extends Check {
	protected $validOptions = array('regex' => true);
	protected $regex;
	protected $defaultTarget = 'target';
	protected $targets = array('target' => null);

	public function getExpr() {
		return new GenericExpr(
			array($this, 'phpExpr'),
			array($this, 'jsExpr'),
			null,
			array($this, 'exGetTargets'),
			$this->targets['target']
		);
	}

	public function phpExpr($indent, $data) {
		return 'preg_match(\''.addslashes($this->regex).'\', \''.$data->getPHP($indent).'\')';
	}

	public function jsExpr($indent, $data) {
		return '('.$data->getJSValue().'.search('.$this->regex.')!=-1)';
	}

	public function exGetTargets($data) {
		return $data->getTargets();
	}

	public function valid($callbacks) {
		return preg_match($this->regex, $this->targets['target']->getValue());
	}
}

?>