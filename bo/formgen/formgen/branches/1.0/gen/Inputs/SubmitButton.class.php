<?php

/*
	Class: HTMLSubmit
	Wraps a form submit button.
	This is not a descendant of HTMLInput, since it does not allow checks and such.
*/
class HTMLSubmit extends HTMLTag {
	public $action;

	public function __construct() {
		parent::__construct('input');
		$this->attributes = array('type' => 'submit');
	}

	public function getName() {
		if (isset($this->attributes['name']))
			return $this->attributes['name'];
		else
			return '';
	}

	public function addAttributes($attribs) {
		parent::addAttributes($attribs);
		if (isset($this->attributes['action'])) {
			$this->action = $this->attributes['action'];
			unset($this->attributes['action']);
		}
	}

	public function addChildren(array $nodes) {
		throw new ParseException('Submit button can\'t have children');
	}
}

/*
	Class: HTMLReset
	Wraps a form reset button.
	This is not a descendant of HTMLInput, since it does not allow checks and such.
*/
class HTMLReset extends HTMLTag {
	public $action;

	public function __construct() {
		parent::__construct('input');
		$this->attributes = array('type' => 'button', 'onclick' => 'formReset(this.form)');
	}

	public function getName() {
		if (isset($this->attributes['name']))
			return $this->attributes['name'];
		else
			return '';
	}

	public function addAttributes($attribs) {
		parent::addAttributes($attribs);
		if (isset($this->attributes['action'])) {
			$this->action = $this->attributes['action'];
			unset($this->attributes['action']);
		}
	}

	public function addChildren(array $nodes) {
		throw new ParseException('Reset button can\'t have children');
	}
}

/*
	Class: HTMLButton
	Wraps a form button.
	This is not a descendant of HTMLInput, since it does not allow checks and such.
*/
class HTMLButton extends HTMLTag {
	public function __construct() {
		parent::__construct('input');
		$this->attributes = array('type' => 'button');
	}

	public function addChildren(array $nodes) {
		throw new ParseException('Button can\'t have children');
	}
}

?>