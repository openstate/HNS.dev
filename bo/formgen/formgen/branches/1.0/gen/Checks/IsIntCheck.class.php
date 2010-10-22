<?php

require_once('Check.class.php');

class IsIntCheck extends RegexCheck {
	protected $validOptions = array();
	protected $regex = '/^-?[0-9]+$/';
	protected $formEl;

	protected $errorMsgName = 'isInt';
}

CheckFactory::register('isInt', 'IsIntCheck');
$GLOBALS['errormsg']['isInt'] = 'Invalid number';

?>