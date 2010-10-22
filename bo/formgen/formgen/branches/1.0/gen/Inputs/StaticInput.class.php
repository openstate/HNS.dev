<?php

/*
	Class: HTMLStaticInput
	An input that cannot be edited. Used to only display form data.
*/
class HTMLStaticHtmlInput extends HTMLInput {
	protected $inputType = 'text';
	protected $values = array();

	public function addValue(Value $value) {
		$this->values[]= $value;
	}

	public function setOnChange($js) { /*throw new ParseException('Can\'t set onchange on a static input');*/ }

	public function getHTML() {
		if ($this->valuesEnum instanceof CustomEnum) {
			if ($this->valuesEnum->getSingleAttr())
				return '{$'.$this->formDataVar.'.'.$this->valuesEnum->getSingleAttr().'}';
			else
				return '{$'.$this->formDataVar.'.'.$this->name.'}';
		}
		if (count($this->values)>0) {
			// Enumerant type, so convert the values.
			$s = '';
			foreach ($this->values as $val) {
				if ($s == '')
					$s = '{if ';
				else
					$s.= '{elseif ';
				$s.= '$'.$this->formDataVar.'.'.$this->name.' == \''.$val->getValue().'\'}'.$val->getContent()->getHTML();
			}
			$s.= '{/if}';
			return $s;
		} else
			return '{$'.$this->formDataVar.'.'.$this->name.'}';
	}
}

class HTMLStaticInput extends HTMLStaticHtmlInput {
	public function getHTML() {
		if (!$this->valuesEnum instanceof CustomEnum && count($this->values)==0)
			return '{$'.$this->formDataVar.'.'.$this->name.'|htmlentities:2:\'UTF-8\'|nl2br}';
		else
			return parent::getHTML();
	}
}

class HTMLStaticDateInput extends HTMLStaticInput {
	private $defaultFormat = '%e %B %Y %H:%M:%S';

	public function getHTML() {
		if (isset($this->attributes['format']))
			$fmt = $this->attributes['format'];
		else
			$fmt = $this->defaultFormat;
		return '{$'.$this->formDataVar.'.'.$this->name.'|date_format:\''.addslashes($fmt).'\'}';
	}
}

HTMLInputFactory::register('static',     'HTMLStaticInput');
HTMLInputFactory::register('statichtml', 'HTMLStaticHtmlInput');
HTMLInputFactory::register('staticdate', 'HTMLStaticDateInput');

?>