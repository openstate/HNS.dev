<?php

class url extends Check {
	protected $defaultTarget = 'target';
	protected $targets = array('target' => null);
	protected $pattern = '/^(?:https?:\/\/[^\/]+)?(?:\/.+)*\/?$/i';

	public function __construct($errorMsg, InputElement $target = null) {
		parent::__construct($errorMsg, $target);		
	}

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
		return 'preg_match(\''.addslashes($this->pattern . 'u').'\', \''.$data->getPHP($indent).'\')';
	}
	
	public function jsExpr($indent, $data) {
		return '('.$data->getJSValue().'.search('.$this->pattern.')!=-1)';
	}
	
	public function exGetTargets($data) {
		return $data->getTargets();
	}

	public function valid($callbacks) {
		return preg_match($this->pattern . 'u', trim($this->targets['target']->getValue()));
	}
}

?>