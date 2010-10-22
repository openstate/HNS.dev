<?php

require_once('Check.class.php');

/*
	Class: RegexCheck
	Check that requires the value to match a regular expression.

	See the manual, <Regex check> for its use.
*/
class RegexCheck extends Check {
	protected $validOptions = array('regex' => true);
	protected $regex;
	protected $formEl;

	public function getPHP($indent) {
		return 'preg_match(\''.addslashes($this->regex).'\', '.$this->formEl->getPHP($indent).')';
	}

	public function getJS($indent, $parser) {
		return '('.$this->formEl->getJS($indent, $parser).'.search('.$this->regex.')!=-1)';
	}

	public function getHTML() {
		return 'preg_match(\''.addslashes($this->regex).'\', '.$this->formEl->getHTML().')';
	}

	public function getExpr(FormElExpr $formEl) {
		$this->formEl = $formEl;
		return new GenericExpr(array($this, 'getPHP'), array($this, 'getJS'), array($this, 'getHTML'));
	}

	protected $errorMsgName = 'regex';
}

CheckFactory::register('regex', 'RegexCheck');
$GLOBALS['errormsg']['regex'] = 'Invalid value';

?>