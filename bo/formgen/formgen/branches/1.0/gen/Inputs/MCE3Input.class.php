<?php

require_once('FCKInput.class.php');

/*
	Class: MCE3TextInput
	Wraps the TinyMCE editor version 3.
*/

class HTMLMCE3Input extends HTMLInput {
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
		return array(new AssignStatement(
			new FormElExpr($this),
			new TernaryExpr(
				new IssetExpr(new FormPostExpr($this->name)),
				new FuncCallExpr('safeHtml',
					new FormPostExpr($this->name)),
				new ValueExpr(null)
			)
		));
	}
}

HTMLInputFactory::register('mce3', 'HTMLMCE3Input');

?>