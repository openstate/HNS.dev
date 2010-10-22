<?php

/*
	Class: HTMLTextInput
	Wraps a plain text input field.
*/
class HTMLTextInput extends HTMLInput {
	protected $inputType = 'text';
	protected $convertFloat = false;

	public function addAttributes($attribs) {
		parent::addAttributes($attribs);
		if (isset($this->attributes['convertfloat'])) {
			$this->convertFloat = true;
			unset($this->attributes['convertfloat']);
		}
	}

	public function setOnChange($js) {
		$this->addAttributes(array('onkeyup' => (isset($this->attributes['onkeyup']) ? $this->attributes['onkeyup'].';' : '').$js));
	}
	public function clearOnChange() {
		unset($this->attributes['onkeyup']);
	}

	public function toFloat() {
		$el = new FormPostExpr($this->name);
		return '((float)str_replace(\',\', \'.\', '.$el->getPHP('').'))';
	}

	public function getConversions() {
		if ($this->convertFloat)
			return array(new AssignStatement(new FormElExpr($this), new GenericExpr(array($this, 'toFloat'), null, null)));
		else
			return array();
	}
}

/*
	Class: HTMLPasswordInput
	Wraps a password input field.
*/
class HTMLPasswordInput extends HTMLTextInput {
	protected $inputType = 'password';
}

/*
	Class: HTMLTextAreaInput
	Wraps a textarea input field.
*/
class HTMLTextAreaInput extends HTMLTextInput {
	public function getHTML() {
		return $this->makeTag(
			'textarea',
			array_merge(array('cols' => 10, 'rows' => 5, 'name' => $this->name), $this->attributes),
			'{$'.$this->formDataVar.'.'.$this->name.'}');
	}
}

HTMLInputFactory::register('text',     'HTMLTextInput');
HTMLInputFactory::register('password', 'HTMLPasswordInput');
HTMLInputFactory::register('textarea', 'HTMLTextAreaInput');

?>