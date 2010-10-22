<?php

/*
	Class: HTMLHiddenInput
	An input that cannot be edited. Used to only display form data.
*/
class HTMLCheckerInput extends HTMLInput {
	protected $required = true;

	public function makeOptional() {}
	public function setOnChange($js) { }
	public function getHTML() { return ''; }
	public function getRequiredCheck() { return null; }
}

HTMLInputFactory::register('checker', 'HTMLCheckerInput');

?>