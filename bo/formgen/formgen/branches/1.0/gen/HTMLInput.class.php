<?php

// File: HTML Input classes

/*
	Class: HTMLInput
	Base class for html inputs.

	New input types should be derived from this class. Note that an input is not limited to
	a single actual HTML input tag.

	Due to the way inputs are created, all HTMLInput constructors must take the
	same parameters as this class.
*/
abstract class HTMLInput extends HTMLTag {
	// Property: $name
	// The name/identifier of this input
	protected $name = '';
	// Property: $checks
	// A list of <Checks> entered values have to adhere to.
	protected $checks = array();
	// Property: $required
	// Determines whether a value must be entered for this input
	protected $required = false;
	// Property: $inputType
	// The value of the 'type' attribute for an <input> tag. If inputs do not
	// consist of a single <input> tag, this property is not relevant since
	// they will have their own implementation of <HTMLNode::getHTML>.
	protected $inputType = '';
	// Property: $default
	// The default value for this input.
	protected $default = '';
	// Property: $formDataVar
	// The name of the variable that holds the pre-filled in values in the template.
	protected $formDataVar;

	protected $formName;

	protected $valuesEnum = null;

	protected $prefix;
	protected $postfix;

	/*
		Constructor: __construct

		Parameters:
		$name - The identifier of this input
	*/
	public function __construct($name, $formName) {
		parent::__construct('input');
		$this->name = $name;
		$this->formName = $formName;
		$this->attributes['id'] = $this->name;
		$this->formDataVar = $GLOBALS['formDataVar'];
	}

	// Method: getName
	// Returns the identifier of this input.
	public function getName() { return $this->name; }
	// Method: addValue
	// For inputs with a limited set of values, adds a valid value. See <HTMLOptionsInput::addValue>.
	public function addValue(Value $value) { throw new Exception('Can\'t add values to a '.get_class($this).' tag'); }
	public function setEnum(Enum $enum)    { $this->valuesEnum = $enum; }
	// Method: setDefault
	// Sets the default value.
	public function setDefault($value) { $this->default = $value; }
	public function getDefaults()      { return array($this->name => $this->default); }
	/*
		Method: setOnChange
	  Adds a piece of javascript to this node's javascript event that triggers when the input is changed.
	  For most inputs, this is *onchange*, but for other inputs it may be different.

	  Parameters:
		$js - The javascript to add.
	*/
	public function setOnChange($js)   {
		$this->addAttributes(array('onchange' => (isset($this->attributes['onchange']) ? $this->attributes['onchange'].';' : '').$js));
	}

	public function clearOnChange() {
		unset($this->attributes['onchange']);
	}

	// Method: makeRequired
	// Forces this input to be required.
	public function makeRequired() {
		if (count($this->checks)==0 && !$this->required)
			$this->setOnChange('revalidate(this.form)');
		$this->required = true;
	}

	public function makeOptional() {
		if ($this->required) {
			$this->clearOnChange();
		}
		$this->required = false;
	}

	// Method: isRequired
	// Returns whether this input is required.
	public function isRequired() { return $this->required; }

	// Method: addCheck
	// Adds a new <Check> that this input's value should adhere to.
	public function addCheck(Check $check) {
		if (count($this->checks)==0 && !$this->required)
			$this->setOnChange('revalidate(this.form)');
		$this->checks[]= $check;
	}

	public function useParser(DescParser $parser) {}

	/*
		Method: setFromProperty
		Sets this node's <$checks>, <$required> and values from a <Property>.

		Parameters:
		$prop - The property to copy values from.
	*/
	public function setFromProperty(Property $prop) {
		$this->checks = $prop->getChecks();
		$this->required = $prop->getRequired();
		if (count($this->checks)>0 || $this->required) {
			$this->setOnChange('revalidate(this.form)');
		}
		$this->valuesEnum = $prop->getEnum();
		foreach ($prop->getValues() as $val)
			$this->addValue($val);
	}

	public function setPrefix($prefix) {
		$this->prefix = $prefix;
	}

	public function setPostfix($postfix) {
		$this->postfix = $postfix;
	}

	public function getHTML() {
		$result = '';

		if ($this->prefix)
			$result .= $this->makeTag('span', array('class' => 'prefix'), $this->prefix);

		$result .= $this->makeTag(
			$this->type,
			array_merge(array('type' => $this->inputType, 'name' => $this->name, 'value' => '{$'.$this->formDataVar.'.'.$this->name.'|htmlentities:2:\'UTF-8\'}'), $this->attributes),
			'');
		
		if ($this->postfix)
			$result .= $this->makeTag('span', array('class' => 'postfix'), $this->postfix);

		return $result;
		/*
			The second parameter (first after :) of htmlentities is normally one of the constants ENT_COMPAT, ENT_QUOTES, or ENT_NOQUOTES.
			Naturally, Smarty interprets this as a string, not as a constant, so we have to use the actual integer values. For reference,
			here they are for PHP 5.2.0:
		  ENT_COMPAT    2
		  ENT_QUOTES    3
		  ENT_NOQUOTES  0
		*/
	}

	/*
		Method: getGroupedChecks
		Returns a list of this node's checks, grouped by error message.
		This is used to reduce the amount of HTML output for errors.

		Returns:
		An associative array of error messages to an array of <Checks>.
	*/
	public function getGroupedChecks() {
		$checks = $this->checks;
		if ($this->required) { // Being required is a check
			$check = $this->getRequiredCheck();
			if ($check)
				array_unshift($checks, $check);
		}
		$byText = array();
		foreach ($checks as $check) {
			if (!isset($byText[$check->getErrorMsg()->getHTML()]))
				$byText[$check->getErrorMsg()->getHTML()] = array();
			$byText[$check->getErrorMsg()->getHTML()][]= $check;
		}
		return $byText;
	}

	/*
		Method: getRequiredCheck
		Separate function to get the required check for this input, needed
		since file inputs need a different check.
	*/
	protected function getRequiredCheck() {
		return new RequiredCheck();
	}

	/*
		Method: getErrorMsgs
		Returns the error messages that can be generated for this input.

		Returns:
		An associative array with the key being the error flag identifier, and the
		value the message to show.
	*/
	public function getErrorMsgs() {
		$byText = $this->getGroupedChecks();

		$result = array();
		$i = 0;
		foreach ($byText as $checks) {
			$result[$this->name.'_'.$i] = reset($checks)->getErrorMsg();
			$i++;
		}

		return $result;
	}

	public function getConditionalStmt($leafCallback, $addRequired = true) {
		$result = call_user_func($leafCallback, $this);

		if (count($result) > 0) {
			$condition = $this->getRealCondition($addRequired);

			if (count($result) == 1) {
				$result = reset($result);
				if ($condition && $result['cond'])
					$cond = new BinaryExpr('&&', $condition, $result['cond']);
				else if ($condition)
					$cond = $condition;
				else
					$cond = $result['cond'];
				$result = array(array('cond' => $cond, 'stmt' => $result['stmt']));
			} else { // count > 1
				$stmt = null;
				$currStmt = null;
				foreach ($result as $item) {
					$newStmt = new IfStatement($item['cond'], $item['stmt']);
					if (!$stmt) {
						$stmt = $newStmt;
						$currStmt = $stmt;
					} else {
						$currStmt->setElse($newStmt);
						$currStmt = $newStmt;
					}
				}
				$result = array(array('cond' => $condition, 'stmt' => $stmt));
			}
		}

		return $result;
	}

	/*
		Method: getRealCondition
		Returns this node's condition corrected for the value of <$required>.

		Since being not required is a condition, the condition under which this
		node is valid must be changed in this case.
	*/
	protected function getRealCondition($addRequired) {
		if ($addRequired && !$this->required) { // Being not required is a condition
			$reqEx = new IsGivenExpr(new FormElExpr($this));
			if ($this->condition)
				return new BinaryExpr('&&', $reqEx, $this->condition);
			else
				return $reqEx;
		} else
			return $this->condition;
	}

	public function getPHPvalue() {
		return '$this->data[\''.$this->name.'\']';
	}

	public function getJSvalue() {
		return 'form[\''.$this->name.'\'].value';
	}

	public function getHTMLvalue($formDataVar) {
		return '$'.$formDataVar.'.'.$this->name;
	}
}

/*
	Class: HTMLOptionsInput
	Base class for multiselect inputs.
	These are inputs where the user can only select from a limited set of values.
	e.g. Radio buttons and dropdown boxes.
*/
class HTMLOptionsInput extends HTMLInput {
	// Property: $values
	// A list of all the valid values
	protected $values = array();

	public function plainClone() {
		$result = parent::plainClone();
		$newVals = array();
		foreach ($result->values as $val)
			$newVals[]= $val->plainClone();
		$result->values = $newVals;
		return $result;
	}

	public function templateClone($params) {
		$res = parent::templateClone($params);
		$newVals = array();
		foreach ($result->values as $val)
			$newVals[]= $val->templateClone($params);
		$res->values = $newVals;
		return $res;
	}

	/*
		Method: addValue
		Adds a new valid value.

		Parameter:
		$value - The value to add
	*/
	public function addValue(Value $value) { $this->values[]= $value; }

	// Method: setOnChange
	// Sets this node's state change javascript event.
	// For most multiselect inputs, this seems to be the *onclick* event.
	public function setOnChange($js) {
		$this->addAttributes(array('onclick' => (isset($this->attributes['onclick']) ? $this->attributes['onclick'].';' : '').$js));
	}
	public function clearOnChange() {
		unset($this->attributes['onclick']);
	}

	public function getConditionalStmt($leafCallback, $addRequired = true) {
		if ($this->valuesEnum instanceof CustomEnum) {
			if ($this->valuesEnum->getCheckFunc()!='') {
				$inEnum = new ServerCheck();
				$inEnum->addOption('function', $this->valuesEnum->getCheckFunc());
				$this->checks[]= $inEnum;
			}
		} else {
			$inEnum = new InEnumCheck();
			$vals = array();
			foreach ($this->values as $val)
				$vals[]= $val->getValue();
			$inEnum->addOption('values', $vals);
			$this->checks[]= $inEnum;
		}

		$res = parent::getConditionalStmt($leafCallback, $addRequired);
		array_pop($this->checks);
		return $res;
	}

	public function getExtraJS($js = '') {
		$js = parent::getExtraJS($js);
		foreach ($this->values as $val)
			$js = $val->getContent()->getExtraJS($js);
		return $js;
	}

	public function setDefault($value) {
		if ($this->valuesEnum instanceof CustomEnum)
			parent::setDefault($value);
		else {
			foreach ($this->values as $val) {
				if ($val->getValue()==$value) {
					parent::setDefault($value);
					return;
				}
			}
			throw new Exception('Given default value ('.$value.') is not a valid value for this input ('.$this->name.')');
		}
	}
}


/*
	Class: HTMLInputFactory.
	The factory to generate inputs.
*/
class HTMLInputFactory {
	/*
		Property: $classes
		A list of registered checks.
		The keys of this array are the names of the checks as used in the form, the
		values are the associated class names.
	*/
	private static $classes = array();

	private function __construct() {}

	/*
		Method: register
		Registers a new input type.

		Parameters:
		$id				 - The name of the input as used in the form.
		$className - The name of the class that handles this input.
	*/
	public static function register($id, $className) {
		if (isset(self::$classes[$id]))
			throw new Exeception('Input type \''.$id.'\' already exists.');
		self::$classes[$id] = $className;
	}

	/*
		Method: create
		Creates a new input.

		Parameters:
		$id   - The name of the input to create.
		$name - The identifier of the input.
	*/
	public static function create($id, $name, $formName) {
		if (!isset(self::$classes[$id]))
			throw new Exception('Unknown input type \''.$id.'\'.');
		$input = new self::$classes[$id]($name, $formName);
		return $input;
	}
}

?>