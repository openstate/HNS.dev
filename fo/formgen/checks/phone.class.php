<?php

class phone extends Check {
	protected $defaultTarget = 'target';
	protected $targets = array('target' => null);
	protected $phonePattern = '/^(?:\+?\d{10,14})?$/i';

	public function __construct($errorMsg, InputElement $target = null) {
		parent::__construct($errorMsg, $target);

		$this->targets['target']->setRequired(false);
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
		return 'preg_match(\''.addslashes($this->phonePattern . 'u').'\', \''.$data->getPHP($indent).'\')';
	}
	
	public function jsExpr($indent, $data) {
		return '('.$data->getJSValue().'.search('.$this->phonePattern.')!=-1)';
	}
	
	public function exGetTargets($data) {
		return $data->getTargets();
	}

	public function valid($callbacks) {
		return preg_match($this->phonePattern . 'u', trim($this->targets['target']->getValue()));
	}
}

?>