<?php

/*
	Class: HTMLDropdownInput
	Wraps a select-list dropdown input.
*/
class HTMLDropdownInput extends HTMLOptionsInput {
	public function getHTML() {
		if ($this->valuesEnum instanceof CustomEnum) {
			$attr = '';
			foreach ($this->attributes as $name => $val)
				$attr.= ' '.$name.'="'.htmlentities($val, ENT_COMPAT, 'UTF-8').'"';
			return '{html_options options=$'.$this->valuesEnum->getOptionsVar().' selected=$'.$this->formDataVar.'.'.$this->name.' name=\''.$this->name.'\''.$attr.'}';
		} else {
			$innerHTML = '';
			foreach ($this->values as $val)
				$innerHTML.= '<option value="'.$val->getValue().'" {if $'.$this->formDataVar.'.'.$this->name.'==\''.$val->getValue().'\'}selected="selected"{/if}>'.$val->getContent()->getHTML()."</option>\n";
			return $this->makeTag('select', array_merge(array('name' => $this->name), $this->attributes), $innerHTML);
		}
	}
}

HTMLInputFactory::register('dropdown', 'HTMLDropdownInput');

?>