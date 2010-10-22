<?php

/*
	Class: HTMLRadioInput
	Wraps a set of radio type inputs.
	Each value is wrapped in a label tag as well.
*/
class HTMLRadioInput extends HTMLOptionsInput {
	private $onChangeJS = '';

	public function setOnChange($js)   {
		$this->onChangeJS = ($this->onChangeJS ? $this->onChangeJS.';' : '').$js;
	}
	public function clearOnChange() {
		$this->onchangeJS = '';
	}

	public function getHTML() {
		$attr = '';
		foreach ($this->attributes as $name => $val)
			$attr.= ' '.$name.'="'.htmlentities($val, ENT_COMPAT, 'UTF-8').'"';

		if ($this->valuesEnum instanceof CustomEnum) {

			$html = '{foreach from=$'.$this->valuesEnum->getOptionsVar().' key=key item=value}'."\n\t".
				'<input type="radio" name="'.$this->name.'" id="'.$this->name.'_{$key}" value="{$key}"{if $'.$this->formDataVar.'.'.$this->name.'==$key} checked="checked"{/if}'.($this->onChangeJS?' onclick="'.$this->onChangeJS.'"':'').' /><label for="'.$this->name.'_{$key}">{$value}</label>'."\n".
				'{/foreach}';
		} else {
			$html = '';
			foreach ($this->values as $val)
				$html.= '<input type="radio" name="'.$this->name.'" id="'.$this->name.'_'.$val->getValue().'" value="'.$val->getValue().
					'"{if $'.$this->formDataVar.'.'.$this->name.'==\''.$val->getValue().'\'} checked="checked"{/if}'.($this->onChangeJS?' onclick="'.$this->onChangeJS.'"':'').' /><label for="'.$this->name.'_'.$val->getValue().'">'.$val->getContent()->getHTML()."</label>\n";
		}
		return $html;
	}

	public function getConditionalStmt($leafCallback, $addRequired = true) {
		$res = parent::getConditionalStmt($leafCallback, $addRequired);

		foreach ($this->values as $val)
			$res = array_merge($res, $val->getContent()->getConditionalStmt($leafCallback, $addRequired));

		if (count($res)==0)
			return $res;
		else if (count($res)==1) {
			$res = reset($res);
			if ($this->condition && $res['cond'])
				$cond = new BinaryExpr('&&', $this->condition, $res['cond']);
			else
				$cond = $res['cond'];
			return array(array('cond' => $cond, 'stmt' => $res['stmt']));
		} else {
			if ($this->condition) {
				$stmt = new GroupStatement(array());
				foreach ($res as $item)
					$stmt->addStatement(new IfStatement($item['cond'], $item['stmt']));

				return array(array('cond' => $this->condition, 'stmt' => $stmt));
			} else
				return $res;
		}
	}

	public function getJSvalue() {
		return 'getRadioValue(form[\''.$this->name.'\'])';
	}
}

HTMLInputFactory::register('radio', 'HTMLRadioInput');

?>