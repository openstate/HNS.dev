<?php

/*
	Class: HTMLCheckInput
	Wraps a checkbox type input.
*/
class HTMLCheckInput extends HTMLInput {
	protected $inputType = 'checkbox';
	protected $default = false;
	protected $label = '';

	public function addAttributes($attr) {
		parent::addAttributes($attr);
		if (isset($this->attributes['label'])) {
			$this->label = $this->attributes['label'];
			unset($this->attributes['label']);
		}
	}

	public function setDefault($value) { $this->default = $value == 'true'; }
	public function setOnChange($js)   {
		$this->addAttributes(array('onclick' => (isset($this->attributes['onclick']) ? $this->attributes['onclick'].';' : '').$js));
	}
	public function clearOnChange() {
		unset($this->attributes['onclick']);
	}

	public function getHTML() {
		return $this->makeTag(
			$this->type,
			array_merge(array('type' => $this->inputType, 'name' => $this->name), $this->attributes),
			'',
			'{if $'.$this->formDataVar.'.'.$this->name.'} checked="checked"{/if}').
			($this->label ? ' <label for="'.$this->name.'">'.$this->label.'</label>'
			 : '');
	}

	protected function getRealCondition($addRequired) {
		return $this->condition; // Checks are never required so we need to leave out the optional part
	}

	public function getConversions() {
		return array(new AssignStatement(new FormElExpr($this), new IssetExpr(new FormPostExpr($this->name))));
	}

	public function getJSvalue() {
		return 'form[\''.$this->name.'\'].checked';
	}
}

HTMLInputFactory::register('check',    'HTMLCheckInput');

?>