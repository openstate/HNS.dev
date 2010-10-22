<?php

/*
	File: Check classes
	Contains the base class to derive <Validation checks> from. Also contains a few
	checks that are used internally and not available in form creations, and the factory
	class to register checks with.
*/

require_once('Expression.class.php');

/*
	Class: Check
	Base class for validation checks.

	Due to the way checks are created, all Check constructors must not
	take any parameters.
*/
abstract class Check {
	/*
		Property: $validOptions
		An array of valid option names for this check type.
		If a key is defined in this array, <addOption> will set a property with that name to
		the given values. This is convenient since most options will be simple name/value
		settings.
	*/
	protected $validOptions = array();

	/*
		Property: $customErrorMsg
		The custom error message for when the check fails.
		Overrides the default if it is not *null*.
	*/
	protected $customErrorMsg = null;

	protected $parser;

	public function __construct(DescParser $parser) {
		$this->parser = $parser;
	}

	/*
		Method: addOption
		Sets an option for a check.
		For each *option* tag this function is called.

		Parameters:
		$name  - The name of the option.
		$value - The value given for the option.
	*/
	public function addOption($name, $value) {
		if (isset($this->validOptions[$name]))
			$this->$name = $value;
		else
			throw new Exception('Unknown option: '.$name);
	}

	/*
		Method: setCustomError
		Specifies a custom error message for the check.

		Parameters:
		$msg - The HTML for the custom error message
	*/
	public function setCustomError(HTMLNode $msg) {
		$this->customErrorMsg = $msg;
	}

	/*
		Method: getExpr
		Returns an <Expression> representing this check.

		Parameters:
		$formEl - The form element this check relates to.
	*/
	abstract public function getExpr(FormElExpr $formEl);

	protected $errorMsgName;
	/*
		Method: getErrorMsg
		Returns a string with a default error message when this check fails.
	*/
	public function getErrorMsg() {
		if ($this->customErrorMsg !== null)
			return $this->customErrorMsg;
		else
			if ($GLOBALS['errormsg'][$this->errorMsgName] instanceof HTMLNode)
				return $GLOBALS['errormsg'][$this->errorMsgName];
			else
				return new HTMLText($GLOBALS['errormsg'][$this->errorMsgName]);
	}
}

/*
	Class: RequiredCheck
	A check that ensures a value is given for a form input.
	Only used internally.
*/
class RequiredCheck extends Check {
	public function __construct() {} // Remove the required parameter

	public function getExpr(FormElExpr $formEl) {
		return new IsGivenExpr($formEl);
	}

	protected $errorMsgName = 'required';
}

/*
	Class: InEnumCheck
	A check that ensures a value is within a limited set of valid values.
	Only used internally.

	Options:
	values - An array of valid values.
*/
class InEnumCheck extends Check {
	// For radios & dropdowns
	protected $validOptions = array('values' => true);
	protected $values;

	public function __construct() {} // Remove the required parameter

	public function getExpr(FormElExpr $formEl) {
		return new ValidEnumExpr($formEl, $this->values);
	}

	protected $errorMsgName = 'inenum';
}

/*
	Class: CheckFactory
	The factory to generate validation checks.
*/
class CheckFactory {
	/*
		Property: $classes
		A list of registered checks.
		The keys of this array are the names of the checks as used in the form, the
		values are the associated class names.
	*/
	private static $classes = array();

	private function __construct() {}

	/*
		Method: register
		Registers a new check type.

		Parameters:
		$id				 - The name of the check as used in the form.
		$className - The name of the class that handles this check.
	*/
	public static function register($id, $className) {
		if (isset(self::$classes[$id]))
			throw new Exeception('Check type \''.$id.'\' already exists.');
		self::$classes[$id] = $className;
	}

	/*
		Method: create
		Creates a new check.

		Parameters:
		$id - The name of the check to create.
	*/
	public static function create($id, DescParser $parser) {
		if (!isset(self::$classes[$id]))
			throw new Exception('Unknown check type \''.$id.'\'.');
		return new self::$classes[$id]($parser);
	}
}

if (!isset($GLOBALS['errormsg'])) {
	$GLOBALS['errormsg'] = array(
		'required' => 'Not filled in',
		'inenum'   => 'Invalid value selected'
	);
}

?>