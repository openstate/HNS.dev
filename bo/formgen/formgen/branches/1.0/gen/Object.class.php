<?php

// File: ObjectDescription

require_once('HTML.class.php');
require_once('Check.class.php');

/*
	Class: Value
	Describes a single value in an <Enum>.
*/
class Value {
	private $value, $content;

	/*
		Constructor: __construct
		Creates a new Value.

		Parameters:
		$value   - The value
		$content - The HTML content associated with the value
	*/
	public function __construct($value, HTMLNode $content) {
		$this->value = $value;
		$this->content = $content;
	}

	public function getValue()   { return $this->value; }
	public function getContent() { return $this->content; }
}

/*
	Class: Enum
	A list of a few <Values>.
*/
class Enum {
	private $values = array();

	public function addValue(Value $value) { $this->values[]= $value; }
	public function getValues() { return $this->values; }
}

class CustomEnum extends Enum {
	private $options = '';  // Smarty var that will contain the values for the dropdown
	private $single = '';   // Object property that will contain the display value in case only the currently selected value is displayed
	private $check = '';    // Check function

	public function __construct($optionsVar, $singleAttr, $checkFunc) {
		$this->options = (string)$optionsVar;
		$this->single  = (string)$singleAttr;
		$this->check   = (string)$checkFunc;
	}

	public function getOptionsVar()  { return $this->options; }
	public function getSingleAttr()  { return $this->single; }
	public function getCheckFunc()   { return $this->check; }

	public function addValue(Value $value) { }
	public function getValues() { return array(); }
}

/*
	Class: Property
	Describes a single object property.
*/
class Property {
	private $id, $read, $write;
	private $caption = '';
	private $values = null;
	private $checks = array();
	private $required = false;

	/*
		Constructor: __construct
		Creates a new Property.

		Parameters:
		$id    - The name of the property.
		$read  - The name of the accessor of the property if it is not the same as the id.
		$write - The name of the setter of the property if it is not the same as the id.

		If the read or write parameter refer to functions, their values should end with *()*.
		If the function or property should be defined within the generated class (instead of
		it being in the described class), prefix the value with *c:*.

		Write-only properties are not allowed.
	*/
	public function __construct($id, $read = false, $write = false) {
		if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+$/', $id))
			throw new Exception('Invalid id given: '.$id);
		if ($read && !preg_match('/^(c:)?[a-zA-Z_][a-zA-Z0-9_]+(\(\))?$/', $read))
			throw new Exception('Invalid reader given: '.$read);
		if ($write && !preg_match('/^(c:)?[a-zA-Z_][a-zA-Z0-9_]+(\(\))?$/', $write))
			throw new Exception('Invalid writer given: '.$write);
		if ($write && !$read)
			throw new Exception('Write-only property specified');
		if (!$read && !$write) {
			$read = $id;
			$write = $id;
		}
		$this->id =    $id;
		$this->read =  $read;
		$this->write = $write;
	}

	/*
		Property: $caption
		The caption of the property.
		Use setCaption and getCaption to edit it.
	*/
	public function setCaption($s) { $this->caption = $s; }
	public function getCaption() { return $this->caption; }

	/*
		Property: $required
		Determines whether this property is required when filling in a form.
		Defaults to false, use makeRequired to set this to true and getRequired to
		query the value.
	*/
	public function makeRequired() { $this->required = true; }
	public function makeOptional() { $this->required = false; }
	public function getRequired() { return $this->required; }

	/*
		Method: addValue
		Adds a valid <Value> for this property.

		Parameters:
		$value - The value to add
	*/
	public function addValue(Value $value) {
		if ($this->values === null)
			$this->values = new Enum();
		$this->values->addValue($value);
	}

	public function setCustomValues($options, $single, $check) {
		$this->values = new CustomEnum($options, $single, $check);
	}

	// Method: getValues
	// Returns the valid values for this property.
	public function getValues() {
		if ($this->values)
			return $this->values->getValues();
		else
			return array();
	}
	public function getEnum() { return $this->values; }

	/*
		Property: $checks
		An array of the <Check classes> that are associated with this property.
		Use addCheck to add more checks, and getCheck to get the array.
	*/
	public function addCheck(Check $check) { $this->checks[]= $check; }
	public function getChecks() { return $this->checks; }

	public function getID() { return $this->id; }

	public function getRead()  { return $this->read; }
	public function getWrite() { return $this->write; }
}

/*
	Class: Object
	Contains an <Object description>.
*/
class Object {
	private $properties = array();
	private $id = '';
	private $name = '';
	private $idProp = null;

	/*
		Constructor: __construct
		Creates a new Object.

		Parameters:
		$name     - The name of the described object
		$idAttrib -	The attribute of the object that uniquely identifies an instance of the
		            object. Used when modifying existing objects.
	*/
	public function __construct($name, $idAttrib) {
		if ($idAttrib!==null && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+(\(\))?$/', $idAttrib))
			throw new Exception('Invalid id given: '.$idAttrib);
		$this->name = $name;
		$this->id = $idAttrib;
		if ($idAttrib !== null) {
			$this->idProp = new Property($idAttrib, $idAttrib);
			$this->idProp->setCaption('id');
		}
	}

	public function addProperty(Property $p) {
		$this->properties[$p->getID()] = $p;
	}

	public function getName() { return $this->name; }
	public function hasID() { return $this->id !== null; }
	public function getIDAttrib() {
		if ($this->id === null)
			throw new Exception('No object definition given, but id attribute requested.');
		return $this->id;
	}

	public function getPropertyNames() { return array_keys($this->properties); }

	public function getProperty($name) {
		if ($name == $this->id)
			return $this->idProp;
		if (!isset($this->properties[$name]))
			throw new Exception('Property '.$name.' does not exist');
		return $this->properties[$name];
	}
}

?>