<?php

require_once('ValidDateCheck.class.php');

/*
	Class: ValidDateOrEmptyCheck
	Check that requires the value to match a regular expression.

	See the manual, <Regex check> for its use.
*/
class ValidDateOrEmptyCheck extends ValidDateCheck {
	
	public function getPHP($indent) {
		$el = $this->formEl->getPHP($indent);
		return '(strftime(\'%Y-%m-%d\', strtotime('.$el.'))=='.$el.' || '.$el.' == \'--\')';
	}

	public function getJS($indent, $parser) {
		$formEl = $this->formEl->getJS($indent, $parser);
		$day   = str_replace($this->formEl->getName(), $this->formEl->getName().'[Day]', $formEl);
		$month = str_replace($this->formEl->getName(), $this->formEl->getName().'[Month]', $formEl);
		$year  = str_replace($this->formEl->getName(), $this->formEl->getName().'[Year]', $formEl);
		return '(isValidDate(makeDateStr(form, \''.$this->formEl->getName().'\')) || (form[\''.$this->formEl->getName().'[Year]\'].value == \'\' && form[\''.$this->formEl->getName().'[Month]\'].value == \'\' && form[\''.$this->formEl->getName().'[Day]\'].value == \'\'))';
	}	
}

CheckFactory::register('validDateOrEmpty', 'ValidDateOrEmptyCheck');
$GLOBALS['errormsg']['validDate'] = 'Invalid date';

?>