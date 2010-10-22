<?php

require_once('DescParser.class.php');
require_once('TemplateParser.class.php');

/*
	Class: Generator
	This class writes the actual PHP and HTML code for a given form description.
*/
class Generator {
	// Property: $formDesc
	// A <DescParser> instance to parse the form descriptions.
	private $formDesc;
	// Property: $compiledDesc
	// An array containing information of a single parsed form description
	private $compiledDesc;
	// Property: $opts
	// An associative array with options for controlling output
	private $opts;
	// Property: $names
	// An array with values that are used in processing PHP templates
	private $names = array(
		'processorclass'   => '',
		'htmltemplate'     => '',
		'htmlbasetemplate' => '',
		'displayfile'      => '',
		'formname'         => ''
	);
	// Property: $validations
	// An array of <Statements> that set error flags if validations fail
	// Property: $postAssigns
	// An array of <Statements> that copy the form data to the internal data array, only if
	// the condition for the inputs are met.
	private $validations, $postAssigns;

	// Property: $isEditForm
	// Indicates that the current form is a form to edit existing instances of the defined object.
	// Property: $isListForm
	// Indicates that the current form is a form to list a selection of instances of the defined object.
	private $pageProps = array(
		'load' => false,
		'loadmany' => false,
		'validate' => false,
		'save' => false);

	/*
		Constructor: __construct

		Parameters:
		$opts - An associative array setting the options for the generator.

		Options:
		The valid options are:

    classDir     - The directory relative to the form file where class files will be written.
		className    - The name of the generated classes. The filenames are based on this as well.
		htmlDir      - The directory relative to the form file where HTML files will be written.
		htmlHeadDir  - The directory relative to the form file where HTML header files will be written. If not specified,
		               the header will be written in the normal HTML file.
		actionTarget - The file that the form will be submitted to.
		actionDir    - The directory relative to the form file where action files will be written.
		actionFiles  - An array of files that perform display and/or actions. The key is the name of the PHP template file,
		               the value is the name of the generated file.
		templateDir  - The directory where the PHP templates can be found.

		The generated filenames classFile, actionFiles and actionTarget have a
		few format specifiers:

		%O - The object name as specified in a form template.
		%o - The object name with the first letter converted to lowercase.
		%A - The action name starting with a capital.
		%a - The action name starting with a lowercase letter.
	*/
	public function __construct($opts) {
		$this->formDesc = new DescParser();
		$this->opts = $opts;
	}

	/*
		Method: applyVars
		A callback function that replaces PHP template variables.
		Do not call directly, it is only public because callbacks need to be public.
	*/
	public function applyVars($match) {
		if (isset($match[3])) {
			$match[3] = substr($match[3], 1); // Cut off pipe at the start
			$params = explode('|', $match[3]);
		} else
			$params = array();
		switch ($match[2]) {
		case 'defaults':
			if ($this->formDesc->getObjectDescription()->hasID())
				$php = $match[1].'\''.$this->formDesc->getObjectDescription()->getIDAttrib().'\' => false,'."\n";
			else
				$php = '';
			foreach ($this->compiledDesc['html']->getDefaults() as $key => $value)
				$php.= $match[1].'\''.$key.'\' => '.var_export($value, true).",\n";
			return $php;

		case 'nulls':
			if ($this->formDesc->getObjectDescription()->hasID())
				$php = $match[1].'\''.$this->formDesc->getObjectDescription()->getIDAttrib().'\' => false,'."\n";
			else
				$php = '';

			foreach ($this->compiledDesc['inputLinks'] as $key => $value)
				$php.= $match[1].'\''.$key.'\' => null,'."\n";
			return $php;

		case 'clearerrflags':
			$php = '';
			foreach ($this->compiledDesc['errorflags'] as $value)
				$php.= $match[1].'\''.$value.'\' => false,'."\n";
			return $php;

		case 'conversions':
			$stmts = $this->compiledDesc['html']->getConversions();
			$php = '';
			foreach ($stmts as $stmt) {
				$php.= preg_replace('/(\$this->data)(\[\'[^\']+\'\])/', '$1$2 = $post$2', $match[1].$stmt->getPHP($match[1]))."\n";
			}
			return $php;

		case 'validations': case 'postassigns':
			if ($match[2]=='postassigns' && $this->pageProps['load'] && $this->pageProps['save']) {
				$id = $this->formDesc->getObjectDescription()->getIDAttrib();
				$php = $match[1].'$this->data[\''.$id.'\'] = $post[\''.$id.'\'];'."\n";
			} else
				$php = '';
			if ($match[2]=='validations')
				$ar = $this->validations;
			else
				$ar = $this->postAssigns;
			foreach ($ar as $stmt) {
				if ($stmt['cond'])
					$s = new IfStatement($stmt['cond'], $stmt['stmt']);
				else
					$s = $stmt['stmt'];
				$stmtphp = $s->getPHP("\t\t");
				if ($match[2]=='postassigns') { // Replace the $this->data with $post
					$stmtphp = str_replace(array('$this->data', '$_this'), array('$post', '$this'), $stmtphp);
				}
				$php.= "\t\t".$stmtphp."\n";
			}
			return $php;

		case 'formactions':
			$submits = $this->compiledDesc['submits'];

			$hasSubmits = count($submits)>0;

			// Check for a default action
			if (array_key_exists('', $submits)) {
				$action = $submits['']->action;
				if (!$action)
					$action = 'action';
				$stmt = new GenericStatement('$this->'.$action.'();', '');
				unset($submits['']);
			} else
				$stmt = null;

			// Add other actions
			foreach ($submits as $submit => $input) {
				$newStmt = new IfStatement(new IssetExpr(new FormPostExpr(preg_replace('/\[.*\]$/', '', $input->getName()))), new GenericStatement('$this->'.$input->action.'();', ''));
				if ($stmt)
					$newStmt->setElse($stmt);
				$stmt = $newStmt;
			}

			if (!$hasSubmits)
				return '';
			else
				return str_replace('$this->data', '$post', $match[1].$stmt->getPHP($match[1]));

		case 'customfuncs':
			$objDesc = $this->formDesc->getObjectDescription();
			$funcs = array();
			foreach ($objDesc->getPropertyNames() as $n) {
				$prop = $objDesc->getProperty($n);
				$f = $prop->getRead();
				if (substr($f, 0, 2)=='c:' && substr($f, -2, 2)=='()')
					$funcs[substr($f, 2, -2)] = true;

				$f = $prop->getWrite();
				if (substr($f, 0, 2)=='c:' && substr($f, -2, 2)=='()')
					$funcs[substr($f, 2, -2)] = true;
			}
			foreach ($this->compiledDesc['submits'] as $submit => $input) {
				if ($submit=='' && $input->action)
					$funcs[$input->action] = true;
				// Note that we skip the default submit - we assume this function is
				// defined in the _processor template.
			}

			$php = '';
			foreach ($funcs as $func => $b) {
				$php.= "\tpublic function {$func}() {\n\t}\n";
			}
			return $php;

		case 'loadstatements':
			$php = '';
			$objDesc = $this->formDesc->getObjectDescription();

			foreach ($this->compiledDesc['inputLinks'] as $prop => $input) {
				$access = $objDesc->getProperty($prop)->getRead();
				$value = $params[0].'[\''.$input.'\']';
				if (substr($access, 0, 2)=='c:') {
					$obj = '$this->';
					$access = substr($access, 2);
				} else
					$obj = $params[1].'->';
				if (substr($access, -2, 2)=='()') {
					$php.= $match[1].$params[0].' = array_merge('.$params[0].', '.$obj.substr($access, 0, -2).'($obj));'."\n";
				} else
					$php.= $match[1].$value.' = '.$obj.$access.';'."\n";
			}

			return $php;

		case 'savestatements':
			$php = '';
			$objDesc = $this->formDesc->getObjectDescription();

			foreach ($this->compiledDesc['inputLinks'] as $prop => $input) {
				$access = $objDesc->getProperty($prop)->getWrite();
				if ($access !== false) {
					if (substr($access, 0, 2)=='c:') {
						$obj = '$this->';
						$access = substr($access, 2);
						$value1 = $params[1];
						$value2 = '';
					} else {
						$obj = $params[1].'->';
						$value1 = $params[0];
						$value2 = '[\''.$input.'\']';
					}
					if (substr($access, -2)=='()')
						$call = substr($access, 0, -2).'('.$value1.')';
					else
						$call = $access.' = '.$value1.$value2;
					$php.= $match[1].(substr($access, -2)!='()' ? 'if (isset('.$value1.$value2.')) ' : '').$obj.$call.';'."\n";
				}
			}

			return $php;

		default:
			if (isset($this->names[$match[2]])) {
				$res = $match[1].$this->names[$match[2]];
				if (count($params)>0 && $params[0] == 'lc')
					$res = strtolower($res);
				return $res;
			} else
				return $match[0];
		}
	}

	/*
		Method: applyConditions
		A callback function that filters out code in PHP templates based on conditions.
		Do not call directly, it is only public because callbacks need to be public.
	*/
	public function applyOldConditions($match) {
		// ifedit, iflist and ifnotlist are old and retained for backward compatibility.
		if ($match[1]=='ifedit') {
			if ($this->pageProps['load'] && $this->pageProps['validate'] && $this->pageProps['save'])
				return $match[2];
			else
				return '';
		} else if ($match[1]=='iflist') {
			if ($this->pageProps['loadmany'])
				return $match[2];
			else
				return '';
		} else if ($match[1]=='ifnotlist') {
			if (!$this->pageProps['loadmany'])
				return $match[2];
			else
				return '';
		} else
			return $match[0];
	}

	public function applyConditions($match) {
		$parts = explode(':', $match[1]);
		if (count($parts)>1 && ($parts[0] == 'if' || $parts[0] == 'ifnot')) {
			$eval = eval('return '.preg_replace('/load(many)?|loadmany|validate|save/', '$this->pageProps[\'$0\']', $parts[1]).';');
			if (($eval && $parts[0]=='if') || (!$eval && $parts[0]=='ifnot'))
				return $match[3];
			else if (isset($match[5]) && $match[5]!='')
				return $match[5];
		} else
			return $match[0];
	}

  /*
		Method: processPHPTemplate
		Takes a PHP template and processes it to produce a proper PHP file.

		Parameters:
		$phpTemplate - The filename of the template to process.
		$outputFile  - The filename to write the result to.
	*/
	protected function processPHPTemplate($phpTemplate, $outputFile) {
		$php = file_get_contents($phpTemplate);
		$php = preg_replace_callback('@{(if(not)?:[a-zA-Z &|!]+)}(.*?)({else}(.*?))?{/if}@s', array($this, 'applyConditions'), $php);
		$php = preg_replace_callback('|{([a-zA-Z]+)}(.*?){/\1}|s', array($this, 'applyOldConditions'), $php);
		$php = preg_replace_callback('/([ \t]*){([a-zA-Z]+)(\|[^}]+)?}/m', array($this, 'applyVars'), $php);
		file_put_contents($outputFile, $php);
	}

	/*
		Method: getErrorStmt
		Generates the statements to set error flags.
		Do not call directly. This is a callback function for <HTMLNode::getConditionalStmt>.
	*/
	public function getErrorStmt(HTMLInput $node) {
		$byText = $node->getGroupedChecks();

		$result = array();
		$i = 0;
		foreach ($byText as $checks) {
			$cond = null;
			foreach ($checks as $check) {
				$checkEx = new UnaryExpr('!', $check->getExpr(new FormElExpr($node)));
				if ($cond!=null)
					$cond = new BinaryExpr('||', $cond, $checkEx);
				else
					$cond = $checkEx;
			}
			$result[]= array('cond' => $cond, 'stmt' => new GenericStatement(
				'$this->errors[\''.$node->getName().'_'.$i.'\'] = true;',
				'{ document.getElementById(\'_err_'.$node->getName().'_'.$i.'\').style.display = \'\'; maySubmit = false; }'));
			$i++;
		}
		return $result;
	}

	/*
		Method: getPostAssignStmt
		Generates the statements to assign submitted data to the internal data array.
		Do not call directly. This is a callback function for <HTMLNode::getConditionalStmt>.
	*/
	public function getPostAssignStmt(HTMLInput $node) {
		if (count($node->getConversions())>0)
			return array();
		else
			return array(array(
				'cond' => new IssetExpr(new FormElExpr($node)),
				'stmt' => new GenericStatement('$_this->data[\''.$node->getName().'\'] = $post[\''.$node->getName().'\'];',
			    ''))); // No JS for this
	}

	/*
		Method: setFile
		Sets the form description file to parse.
	*/
	public function setFile($filename) {
		$this->formDesc->setFile($filename);
	}

	/*
		Method: setOpts
		Changes the value of options. See <__construct> for valid options and their meanings.
	*/
	public function setOpts($opts) {
		$this->opts = array_merge($this->opts, $opts);
	}

	/*
		Method: compileFiles
		Processes a form description and writes out the generated files.
	*/
	public function compileFiles() {
		$this->names['editclass']     = $this->formDesc->getObjectDescription()->getName();
		$this->names['formid']        = '\'Form_'.$this->formDesc->getObjectDescription()->getName().'\'';

		$this->validations = array();

		$forms = $this->formDesc->getDescribedForms();
		if (in_array('createform', $forms)) {
			$this->pageProps = array('load' => false, 'loadmany' => false, 'validate' => true, 'save' => true);
			$this->compileFile('createform', 'Create');
		}
		if (in_array('editform', $forms)) {
			$this->pageProps = array('load' => true, 'loadmany' => false, 'validate' => true, 'save' => true);
			$this->compileFile('editform', 'Edit');
		}
		if (in_array('list', $forms)) {
			$this->pageProps = array('load' => false, 'loadmany' => true, 'validate' => false, 'save' => false);
			$this->compileFile('list', 'List');
		}
		if (in_array('view', $forms)) {
			$this->pageProps = array('load' => true, 'loadmany' => false, 'validate' => false, 'save' => false);
			$this->compileFile('view', 'View');
		}
	}

	/*
		Method: formatFile
		Replaces the %O, %o, %A and %a placeholders for action file names.

		Parameters:
		$filename - The filename to format.
		$action   - The value for the $a placeholder.

		Returns:
		The formatted filename.
	*/
	private function formatFile($filename, $action) {
		$lcAction = $action;
		$lcAction[0] = strtolower($lcAction[0]);
		$lcObject = $this->formDesc->getObjectDescription()->getName();
		$lcObject[0] = strtolower($lcObject[0]);
		return str_replace(
			array('%O', '%o', '%A', '%a'),
			array(
				$this->formDesc->getObjectDescription()->getName(),
				$lcObject,
				$action,
				$lcAction
			),
			$filename);
	}

	/*
		Method: compileFile
		Generates files for the description of a single form.

		Parameters:
		$form   - The name of the form description
		$action - The name of the action performed by the form
	*/
	protected function compileFile($form, $action) {
		$GLOBALS['formDataVar'] = 'formdata';
		$obj = $this->formDesc->getObjectDescription();
		$formName = $obj->getName().$action;

		$this->compiledDesc = $this->formDesc->getFormDescription($form, $formName);
		if ($action!='List' && $action!='View' && count($this->compiledDesc['submits'])==0)
			throw new ParseException('No submit buttons in form '.$form);

		$htmlFileName = ($this->compiledDesc['formname'] ? $this->compiledDesc['formname'] : $obj->getName().'_'.$action);
		$this->names['processorclass']   = ($this->compiledDesc['formname'] ? $this->compiledDesc['formname'] : $this->formatFile($this->opts['className'], $action));
		$this->names['htmltemplate']     = $htmlFileName.'.html';
		$this->names['htmlbasetemplate'] = $htmlFileName.'Base.html';
		$this->names['action']           = $action;
		$this->names['formname']         = $formName;
		if ($obj->hasID())
			$this->names['id']             = $obj->getIDAttrib();

		$this->validations = $this->compiledDesc['html']->getConditionalStmt(array($this, 'getErrorStmt'));
		$this->postAssigns = $this->compiledDesc['html']->getConditionalStmt(array($this, 'getPostAssignStmt'), false);

		$this->assignOnChange($this->compiledDesc['html'], $this->collectDependencies($this->compiledDesc['html']));

		$html = '<a name="'.$formName.'"></a>';
		$htmlHead = '';
		if ($this->pageProps['validate'])
			$htmlHead = $this->getJavascript($this->compiledDesc['html'], $action);
		if ($this->pageProps['save']) {
			$html.=
				'<form action="'.$this->formatFile($this->opts['actionTarget'], $action).'" name="'.$formName.'" method="post" onsubmit="return formSubmit(this)" enctype="multipart/form-data">'."\n";
		} else if (count($this->compiledDesc['submits'])>0) {
			$html.=
				'<form action="'.$this->formatFile($this->opts['actionTarget'], $action).'" name="'.$formName.'" method="post" enctype="multipart/form-data">'."\n";
		} else
			$html.=
				'<form action="" name="'.$formName.'">'."\n";

		if ($this->pageProps['load'] && $this->pageProps['save'])
			$html.= '<input type="hidden" name="'.$obj->getIDAttrib().'" value="{$formdata.'.$obj->getIDAttrib().'}" />'."\n";

		$html.= $this->compiledDesc['html']->getHTML()."\n".'</form>';

		if ($this->pageProps['validate'])
			$html.= '<script type="text/javascript"><!--'."\n".'updateVisibility(document.forms[\''.$formName.'\']) //--></script>';

		// HTML template
		if (isset($this->opts['htmlHeadDir'])) {
			if ($htmlHead!='') {
				file_put_contents($this->opts['htmlHeadDir'].'/'.$htmlFileName.'Base.html', $htmlHead);
				if (!file_exists($this->opts['htmlHeadDir'].'/'.$htmlFileName.'.html'))
					file_put_contents($this->opts['htmlHeadDir'].'/'.$htmlFileName.'.html', '{include file="`$smartyData.headerDir`/'.$htmlFileName.'Base.html"}');
			}
		} else
			$html = $htmlHead."\n".$html;
		file_put_contents($this->opts['htmlDir'].'/'.$htmlFileName.'Base.html', $html);
		if (!file_exists($this->opts['htmlDir'].'/'.$htmlFileName.'.html')) {
			if (file_exists($this->opts['templateDir'].'/_content.html'))
				$this->processPHPTemplate(
					$this->opts['templateDir'].'/_content.html',
					$this->opts['htmlDir'].'/'.$htmlFileName.'.html');
			else
				file_put_contents($this->opts['htmlDir'].'/'.$htmlFileName.'.html', '{include file="`$smartyData.contentDir`/'.$this->names['htmlbasetemplate'].'"}');
		}

		// Display/action
		foreach ($this->opts['actionFiles'] as $fn => $targetName) {
			$this->processPHPTemplate(
				$this->opts['templateDir'].'/'.$fn,
				$this->opts['actionDir'].'/'.$this->formatFile($targetName, $action)
			);
		}

		// Base class file
		$this->processPHPTemplate(
			$this->opts['templateDir'].'/_FormProcessorBase.class.php',
			$this->opts['classDir'].'/'.$this->names['processorclass'].'Base.class.php'
		);
		// Custom class file
		$customFile = $this->opts['classDir'].'/'.$this->names['processorclass'].'.class.php';
		if (!file_exists($customFile)) // Don't overwrite the custom file if it already exists
			$this->processPHPTemplate($this->opts['templateDir'].'/_FormProcessor.class.php', $customFile);
	}

	// Property: $visID
	// A counter that is used to generate unique IDs for HTML nodes that have no id already assigned.
	protected $visID;
	// Property: $jsVars
	// An array of variables used in processing the JavaScript template.
	protected $jsVars;

	public function applyJSVars($match) {
		if (isset($this->jsVars[$match[2]]))
			return $match[1].$this->jsVars[$match[2]];
		else
			return $match[0];
	}

	/*
		Method: processJSTemplate
		Processes a JavaScript template file.

		Parameters:
		$jsTemplate - The name of the template file.

		Returns:
		A string with the processed javascript.
	*/
	protected function processJSTemplate($jsTemplate) {
		$js = file_get_contents($jsTemplate);
		$js = preg_replace_callback('/([ \t]*){([^\s|}]+)}/m', array($this, 'applyJSVars'), $js);
		return $js;
	}

	/*
		Method: getJavascript
		Returns the block of javascript code for this form.

		Parameters:
		$node   - The base HTML node to generate JavaScript for

		Returns:
		A string with Smarty escaped javascript code.
	*/
	protected function getJavascript(HTMLTag $node) {
		$this->visID = 0;
		$this->jsVars = array(
			'visibility' => $this->getVisibilityStatements($node),
			'errorclear' => $this->getErrorClearStatements(),
			'validation' => $this->getValidationStatements(),
			'extra'      => $node->getExtraJS(),
			'formname'   => $this->names['formname']
		);
		return "{literal}<script type=\"text/javascript\">\n<!--\n".$this->processJSTemplate($this->opts['templateDir'].'/_js.js')."// -->\n</script>{/literal}";
	}

	/*
		Method: getVisibilityStatements
		Returns JavaScript statements to show/hide or enable/disable parts of the form based on
		conditions in the form.

		Parameters:
		$node - The HTML node to process

		Returns:
		A string of javascript code.
	*/
	protected function getVisibilityStatements(HTMLTag $node) {
		if ($node->getCondition()) {
			$id = $node->getAttributes();
			if (isset($id['id']))
				$id = $id['id'];
			else {
				$id = '_vis_'.$this->visID;
				$this->visID++;
				$node->addAttributes(array('id' => $id));
			}
			if ($node->disableMethod == HTMLTag::Hide)
				$result = 'document.getElementById(\''.$id.'\').style.display = '.$node->getCondition()->getJS("\t\t", $this->formDesc).' ? \'\' : \'none\' ;'."\n";
			else
				$result = 'disableTag(document.getElementById(\''.$id.'\'), !('.$node->getCondition()->getJS("\t\t", $this->formDesc).'));'."\n";
		} else
			$result = '';
		foreach ($node->getChildren() as $child) {
			if ($child instanceof HTMLTag)
				$result.= $this->getVisibilityStatements($child);
		}
		return $result;
	}

	/*
		Method: assignOnChange
		Updates the change events for inputs.
		If conditions depend on an input, then when that input changes it must call the JavaScript function
		to update visibility or enable/disable inputs.

		Parameters:
		$node         - The node to process.
		$dependencies -	An array of dependencies as output by <collectDependencies>.
	*/
	protected function assignOnChange(HTMLTag $node, array $dependencies) {
		if ($node instanceof HTMLInput && in_array($node->getName(), $dependencies)) {
			$node->setOnChange('updateVisibility(this.form)');
		}
		foreach ($node->getChildren() as $child) {
			if ($child instanceof HTMLTag)
				$this->assignOnChange($child, $dependencies);
		}
	}

	/*
		Method: collectDependencies
		Determines which inputs are depended on by the conditions in the form.

		Parameters:
		$node - The node to process.

		Returns:
		An array with names of inputs that are depended on.
	*/
	protected function collectDependencies(HTMLTag $node) {
		$cond = $node->getCondition();
		if ($cond)
			$deps = $this->getExprDeps($cond);
		else
			$deps = array();
		foreach ($node->getChildren() as $child) {
			if ($child instanceof HTMLTag)
				$deps = array_merge($deps, $this->collectDependencies($child));
		}
		return $deps;
	}

	/*
		Method: getExprDeps
		Determines which inputs are depended on by an <Expression>.

		Parameters:
		$ex - The expression to process.

		Returns:
		An array with names of inputs that are depended on.
	*/
	protected function getExprDeps(Expression $ex) {
		if ($ex instanceof FormElExpr) {
			$deps = array($ex->getName());
		} else if ($ex instanceof ChildExpr) {
			$deps = array();
			foreach ($ex->getChildren() as $child)
				$deps = array_merge($deps, $this->getExprDeps($child));
		} else
			$deps = array();
		return $deps;
	}

	/*
		Method: getErrorClearStatements
		Returns JavaScript code that hides all error statements.
	*/
	protected function getErrorClearStatements() {
		$js = '';
		foreach ($this->compiledDesc['errorflags'] as $value)
			$js.= "\t\t".'document.getElementById(\'_err_'.$value.'\').style.display = \'none\';'."\n";
		return $js;
	}

	/*
		Method: getValidationStatements
		Returns JavaScript code that validates the form and shows errors if necessary.
	*/
	protected function getValidationStatements() {
		$stmts = $this->validations;
		$js = '';
		foreach ($stmts as $stmt) {
			if ($stmt['cond'])
				$s = new IfStatement($stmt['cond'], $stmt['stmt']);
			else
				$s = $stmt['stmt'];
			$js.= "\t\t".$s->getJS("\t\t", $this->formDesc)."\n";
		}
		return $js;
	}
}

?>