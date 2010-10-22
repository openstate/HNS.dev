<?php

/*
	Class: HTMLHiddenInput
	An input that cannot be edited. Used to only display form data.
*/
class HTMLHiddenInput extends HTMLInput {
	protected $inputType = 'hidden';

	public function setOnChange($js) { /*throw new ParseException('Can\'t set onchange on a hidden input');*/ }
/*
	public function getHTML() {
		return $this->makeTag('input', array_merge(array('name' => $this->name), $this->attributes), '');
	}
*/
}

HTMLInputFactory::register('hidden', 'HTMLHiddenInput');

?>