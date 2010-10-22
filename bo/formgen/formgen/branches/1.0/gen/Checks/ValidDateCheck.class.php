<?php

require_once('Check.class.php');

/*
	Class: ValidDateCheck
	Check that requires the value to match a regular expression.

	See the manual, <Regex check> for its use.
*/
class ValidDateCheck extends Check {
	protected $formEl;

	public function getPHP($indent) {
		$el = $this->formEl->getPHP($indent);
		return '(strftime(\'%Y-%m-%d\', strtotime('.$el.'))=='.$el.')';
	}

	public function getJS($indent, $parser) {
		$formEl = $this->formEl->getJS($indent, $parser);
		$day   = str_replace($this->formEl->getName(), $this->formEl->getName().'[Day]', $formEl);
		$month = str_replace($this->formEl->getName(), $this->formEl->getName().'[Month]', $formEl);
		$year  = str_replace($this->formEl->getName(), $this->formEl->getName().'[Year]', $formEl);
		return '(isValidDate(makeDateStr(form, \''.$this->formEl->getName().'\')))';
	}

	public function getHTML() {
		return 'true';
	}

	public function getExpr(FormElExpr $formEl) {
		$this->formEl = $formEl;
		return new GenericExpr(array($this, 'getPHP'), array($this, 'getJS'), array($this, 'getHTML'));
	}

	protected $errorMsgName = 'validDate';
}

CheckFactory::register('validDate', 'ValidDateCheck');
$GLOBALS['errormsg']['validDate'] = 'Invalid date';

?>