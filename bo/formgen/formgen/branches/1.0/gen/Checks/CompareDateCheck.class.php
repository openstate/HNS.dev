<?php

require_once('Check.class.php');

/*
	Class: CompareDateCheck
	Check that compares a date with a target.

	See the manual, <Regex check> for its use.
*/
class CompareDateCheck extends Check {
	protected $validOptions = array('op' => true, 'value' => true, 'input' => true, 'var' => true);

	protected $formEl;

	protected $op  = false;
	protected $tgt = false;
	protected $tgtType = false;

	public function addOption($name, $value) {
		if (in_array($name, array('value', 'input', 'var'))) {
			if ($this->tgtType)
				throw new Exception('Can only compare to one of a value, input or var on a date comparison.');
			$this->tgtType = $name;
			$this->tgt = $value;
		} else
			parent::addOption($name, $value);
	}

	public function getPHP($indent) {
		$el = $this->formEl->getPHP($indent);

		if ($this->tgtType == 'input') {
			$tgtInput = new FormElExpr($this->parser->getInput($this->tgt));
			$tgt = $tgtInput->getPHP($indent);
		} else if ($this->tgtType == 'var') {
			$tgt = '$this->'.$this->tgt;
		} else if ($this->tgtType == 'value') {
			$tgt = '\''.$this->tgt.'\'';
		}

		return '('.$el.$this->op.$tgt.')';
	}

	public function getJS($indent, $parser) {
		$formEl = $this->formEl->getJS($indent, $parser);

		if ($this->tgtType == 'input') {
			$tgtInput = $this->parser->getInput($this->tgt);
			$tgtStr = new FormElExpr($tgtInput);
			$tgtStr = $tgtStr->getJS($indent, $parser);

			$tgt = 'makeDateStr(form, \''.$tgtInput->getName().'\')';
		} else if ($this->tgtType == 'var') {
			$tgt = '\'{/literal}{$'.$this->tgt.'}{literal}\'';
		} else if ($this->tgtType == 'value') {
			$tgt = '\''.$this->tgt.'\'';
		}

		return '(makeDateStr(form, \''.$this->formEl->getName().'\')'.$this->op.$tgt.')';
	}

	public function getHTML() {
		return 'true';
	}

	public function getExpr(FormElExpr $formEl) {
		if (!$this->op)
			throw new Exception('No operator given for a date comparison');
		if (!$this->tgtType)
			throw new Exception('No target value or input given for a date comparison');
		$this->formEl = $formEl;
		return new GenericExpr(array($this, 'getPHP'), array($this, 'getJS'), array($this, 'getHTML'));
	}

	protected $errorMsgName = 'validDate';
}

CheckFactory::register('compareDate', 'CompareDateCheck');

?>