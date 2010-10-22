<?php

require_once('Check.class.php');

/*
	Class: ServerCheck
	Server-side only check.

	See the manual, <Server check> for its use.
*/
class ServerCheck extends Check {
	protected $validOptions = array('function' => true);
	protected $function;

	public function __construct() {
	}

	public function getPHP($indent) {
		$el = $this->formEl->getPHP($indent);
		return '$this->'.$this->function.'()';
	}

	public function getJS($indent, $parser) {
		return 'true';
	}

	public function getHTML() {
		return 'true';
	}

	public function getExpr(FormElExpr $formEl) {
		$this->formEl = $formEl;
		return new GenericExpr(array($this, 'getPHP'), array($this, 'getJS'), array($this, 'getHTML'));
	}

	protected $errorMsgName = 'server';
}

CheckFactory::register('server', 'ServerCheck');
$GLOBALS['errormsg']['server'] = 'Invalid value';

?>