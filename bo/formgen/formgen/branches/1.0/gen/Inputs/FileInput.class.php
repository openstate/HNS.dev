<?php

// ---
class IsFileGivenExpr extends UnaryExpr {
	public function __construct(Expression $child) {
		parent::__construct('', $child);
	}

	public function getPHP($indent) {
		$php = $this->child->getPHP($indent);
		$php = str_replace('[\'name\']', '', $php);

		return '(isset('.$php.'[\'name\']) && isset('.$php.'[\'error\']) && ('.$php.'[\'error\']==UPLOAD_ERR_OK))';
	}

	public function getJS($indent, DescParser $parser) {
		$php = $this->child->getJS($indent, $parser);
		return $php.'!=\'\'';
	}

	public function getHTML() {
		return 'false';
	}
}

// ---
class FileRequiredCheck extends Check {
	public function __construct() {}

	public function getExpr(FormElExpr $formEl) {
		return new IsFileGivenExpr($formEl);
	}

	protected $errorMsgName = 'required';
}

// ---
class HTMLFileInput extends HTMLInput {
	protected $inputType = 'file';

	protected $hasFileMsg = '';

	public function __construct($name, $formName) {
		parent::__construct($name, $formName);
		if (isset($GLOBALS['errormsg']['hasfilemsg']))
			$this->hasFileMsg = $GLOBALS['errormsg']['hasfilemsg'];
	}

	public function addAttributes($attribs) {
		parent::addAttributes($attribs);

		if (isset($this->attributes['hasfilemsg'])) {
			$this->hasFileMsg = $this->attributes['hasfilemsg'];
			unset($this->attributes['hasfilemsg']);
		}
	}

	public function getRequiredCheck() {
		return new FileRequiredCheck();
	}

	public function conversionPHP($indent) {
		return '$this->data[\''.$this->name.'\']';
	}

	public function filesSet($indent) {
		return '$_FILES[\''.$this->name.'\'][\'name\']';
	}

	public function getConversions() {
		return array(
			new IfStatement(
				new BinaryExpr('&&',
					new IssetExpr(new FormFilesExpr($this->name)),
					new BinaryExpr('!=',
						new GenericExpr(array($this, 'filesSet'), null, null),
						new ValueExpr('')
					)
				),
				new AssignStatement(new GenericExpr(array($this,'conversionPHP'), null, null), new FormFilesExpr($this->name)))
			);
	}

	public function getPHPvalue() {
		return '$this->data[\''.$this->name.'\'][\'name\']';
	}
/*
	public function getJSvalue() {
		return 'form[\''.$this->name.'\'].value';
	}
*/
	public function getHTMLvalue($formDataVar) {
		return '\'\'';
	}


	public function getHTML() {
		return ($this->hasFileMsg ? '{if $'.$this->formDataVar.'.'.$this->name.' != \'\'}'.$this->hasFileMsg.'{/if}' : '').parent::getHTML();
	}
}

HTMLInputFactory::register('file', 'HTMLFileInput');

?>