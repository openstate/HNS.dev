<?php

require_once('RegexCheck.class.php');

/*
	Class: EmailCheck
	Check that validates an email address.

	See the manual, <Email check> for its use.
*/
class EmailCheck extends RegexCheck {
	protected $validOptions = array();
	protected $regex = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{}|~-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/';
	protected $formEl;

	protected $errorMsgName = 'email';
}

CheckFactory::register('email', 'EmailCheck');
$GLOBALS['errormsg']['email'] = 'Invalid email address';

?>