<?php

/*
	Class: HTMLHiddenInput
	An input that cannot be edited. Used to only display form data.
*/
class HTMLUseInput extends HTMLInput {
	protected $inputType = 'hidden';

	public function setOnChange($js) { /*throw new ParseException('Can\'t set onchange on a use input');*/ }

	public function getHTML() { return ''; }

}

HTMLInputFactory::register('use', 'HTMLUseInput');

?>