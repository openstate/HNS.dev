<?php

require_once('Check.class.php');

/*
	Class: NotEqualCheck
	Check that requires a field to be not equal to another field.

	See the manual, <NotEqual check> for its use.
*/
class NotEqualCheck extends Check {
	protected $validOptions = array('compareWith' => true);
	protected $compareWith;

	public function getExpr(FormElExpr $formEl) {
		return new BinaryExpr('!=', $formEl, new FormElExpr($this->parser->getInput($this->compareWith)));
	}

	protected $errorMsgName = 'notequal';
}

CheckFactory::register('notequal', 'NotEqualCheck');
$GLOBALS['errormsg']['notequal'] = 'Values match';

?>