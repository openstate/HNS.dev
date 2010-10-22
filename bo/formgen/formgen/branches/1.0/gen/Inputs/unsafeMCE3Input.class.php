<?php

require_once('FCKInput.class.php');

/*
	Class: UnsafeMCE3TextInput
	Wraps the TinyMCE editor version 3. without stripping javascript
*/

class HTMLUnsafeMCE3Input extends HTMLInput {
	public function getHTML() {
		return $this->makeTag(
			'textarea',
			array_merge(array('cols' => 10, 'rows' => 5, 'name' => $this->name), $this->attributes),
			'{$'.$this->formDataVar.'.'.$this->name.'|htmlentities:2:\'UTF-8\'}');
	}

	public function getJSvalue() {
		return 'tinyMCE.get(\''.$this->name.'\').getContent()';
	}

	public function getConversions() {
		return array();
	}
}

HTMLInputFactory::register('unsafemce3', 'HTMLUnsafeMCE3Input');

?>