<?php

class required extends Check {
	protected $defaultTarget = 'target';
	protected $targets = array('target' => null);

	public function __construct($errorMsg, InputElement $target = null) {
		parent::__construct($errorMsg, $target);

		$this->targets['target']->setRequired(true);
	}

	public function getExpr() {
		return new IsGivenExpr(new InputExpr($this->targets['target']));
	}

	public function valid($callbacks) {
		return $this->targets['target']->getValue() !== '';
	}
}

?>