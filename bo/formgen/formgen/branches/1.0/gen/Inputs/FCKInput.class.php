<?php

/*
	Class: FCKTextInput
	Wraps the FCK editor.
*/

class FuncCallExpr extends UnaryExpr {
	private $funcName;

	public function __construct($funcName, Expression $child) {
		parent::__construct('', $child);
		$this->funcName = $funcName;
	}

	public function getPHP($indent) {
		$php = $this->child->getPHP($indent);
		return $this->funcName.'('.$php.')';
	}

	public function getJS($indent, DescParser $parser) {
		$php = $this->child->getJS($indent, $parser);
		return $this->funcName.'('.$php.')';
	}

	public function getHTML() {
		$php = $this->child->getHTML($indent);
		return $this->funcName.'('.$php.')';
	}
}

class HTMLFCKInput extends HTMLInput {
	protected $attrMap = array(
		'width'  => 'Width',
		'height' => 'Height',
		'toolbarset' => 'ToolbarSet',
		'skinpath' => 'Config[\'SkinPath\']'
	);

	public function getHTML() {
		$php = '		{php}
			require_once(\'fckeditor.php\');
			$editor = new FCKeditor(\''.$this->name.'\');';
		foreach ($this->attributes as $attr => $val) {
			if (isset($this->attrMap[$attr]))
				$php.= "\n\t\t\t".'$editor->'.$this->attrMap[$attr].' = \''.$val.'\';';
		}
		$php.= '
			$editor->Value = $this->_tpl_vars[\''.$this->formDataVar.'\'][\''.$this->name.'\'];
			echo $editor->CreateHtml();
		{/php}';

		return $php;
	}

	public function getExtraJS($js = '') {
		if (!isset($this->attributes['onchange']) || $this->attributes['onchange'] == '')
			return $js;

		$onchange = str_replace('this.form', 'document.forms[\''.$this->formName.'\']', $this->attributes['onchange']);

		$content = "\n\t".'if (editor.Name==\''.$this->name.'\')
		editor.Events.AttachEvent(\'OnSelectionChange\', function() { '.$onchange.' });';
		$new = preg_replace('/function FCKeditor_OnComplete\(editor\) \{/', '$0'.$content, $js, -1, $count);
		if ($count == 0) {
			return $js."\n".'function FCKeditor_OnComplete(editor) {'.$content."\n}";
		} else {
			return $new;
		}
	}

	public function getJSvalue() {
		return 'FCKeditorAPI.GetInstance(\''.$this->name.'\').GetXHTML()';
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

HTMLInputFactory::register('fck', 'HTMLFCKInput');

?>