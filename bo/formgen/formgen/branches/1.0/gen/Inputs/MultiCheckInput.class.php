<?php

/*
	Class: HTMLRadioInput
	Wraps a set of radio type inputs.
	Each value is wrapped in a label tag as well.
*/
class HTMLMultiCheckInput extends HTMLOptionsInput {
	private $onChangeJS = '';
	private $columns = 1;

	public function setOnChange($js)   {
		$this->onChangeJS = $js;
	}
	public function clearOnChange() {
		$this->onChangeJS = '';
	}

	public function getHTML() {
		$attr = '';
		foreach ($this->attributes as $name => $val)
			if ($name != 'columns')
				$attr.= ' '.$name.'="'.htmlentities($val, ENT_COMPAT, 'UTF-8').'"';
			else
				$this->columns = (int)$val;

		if ($this->valuesEnum instanceof CustomEnum) {
			$html = '{foreach from=$'.$this->valuesEnum->getOptionsVar().' key=key item=value}'."\n\t".
				'<input type="checkbox" name="'.$this->name.'[{$key}]" id="'.$this->name.'_{$key}" {if $'.$this->formDataVar.'.'.$this->name.'[$key]} checked="checked"{/if}'.($this->onChangeJS?' onclick="'.$this->onChangeJS.'"':'').' /><label for="'.$this->name.'_{$key}">{$value}</label>'."\n";
			if ($this->columns > 1) {
				$html.= '{cycle values=\'';
				for ($i=1; $i<$this->columns; $i++)
					$html.= '</td><td>,';
				$html.= '</td></tr><tr><td>\'}';
			} else {
				$html.= '<br />';
			}
			$html.= '{/foreach}';
			if ($this->columns > 1) {
				$html = '<table><tr><td>'.$html.'</td></tr></table>';
			}
		} else {
			$html = '';
			foreach ($this->values as $val)
				$html.= '<input type="checkbox" name="'.$this->name.'['.$val->getValue().']" id="'.$this->name.'_'.$val->getValue().'" value="'.$val->getValue().
					'"{if $'.$this->formDataVar.'.'.$this->name.'.'.$val->getValue().'} checked="checked"{/if}'.($this->onChangeJS?' onclick="'.$this->onChangeJS.'"':'').' /><label for="'.$this->name.'_'.$val->getValue().'">'.$val->getContent()->getHTML()."</label>\n";
		}
		return $html;
	}

	public function getConditionalStmt($leafCallback, $addRequired = true) {
		$val = $this->valuesEnum;
		$this->valuesEnum = new CustomEnum('', '', '');
		$result = parent::getConditionalStmt($leafCallback, $addRequired);
		$this->valuesEnum = $val;
		return $result;
	}

	public function getJSvalue() {
		return 'true';
	}
}

HTMLInputFactory::register('multicheck', 'HTMLMultiCheckInput');

?>