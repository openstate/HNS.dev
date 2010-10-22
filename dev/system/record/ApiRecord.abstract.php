<?php

abstract class ApiRecord extends Record {

	/* Get an instance of a record object by name */
	public static function getInstance($name) {
		$name = ucfirst(strtolower($name));
		include_once($name.'.class.php');
		if (!class_exists($name, false))
			throw new RecordException('Unknown record class '.$name);
		$obj = new $name();
		if (!($obj instanceof ApiRecord))
			throw new RecordException('Unknown record class '.$name);
		return $obj;
	}
}

?>