<?php

require_once('Check.class.php');

/*
	Class: RegexCheck
	Check that requires the value to match a regular expression.

	See the manual, <Regex check> for its use.
*/
class IsFloatCheck extends RegexCheck {
	protected $validOptions = array();
	protected $regex = '/^\d+([.,]\d+)?$/';
	protected $formEl;

	protected $errorMsgName = 'isFloat';
}

CheckFactory::register('isFloat', 'IsFloatCheck');
$GLOBALS['errormsg']['isFloat'] = 'Invalid number';

?>