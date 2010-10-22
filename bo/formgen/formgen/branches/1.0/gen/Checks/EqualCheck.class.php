<?php

require_once('Check.class.php');

/*
	Class: EqualCheck
	Check that requires a field to be equal to another field.

	See the manual, <Equal check> for its use.
*/
class EqualCheck extends Check {
	protected $validOptions = array('compareWith' => true);
	protected $compareWith;

	public function getExpr(FormElExpr $formEl) {
		return new BinaryExpr('==', $formEl, new FormElExpr($this->parser->getInput($this->compareWith)));
	}

	protected $errorMsgName = 'equal';
}

CheckFactory::register('equal', 'EqualCheck');
$GLOBALS['errormsg']['equal'] = 'Values do not match';

?>