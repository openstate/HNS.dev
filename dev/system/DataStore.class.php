<?php

class DataStoreException extends Exception { }

class DataStore {
	protected static $inst = null;

	protected static function inst() {
		$class = __CLASS__;
		if (!self::$inst)
			self::$inst = new $class();
		return self::$inst;
	}
	
	protected function __construct() { }

	protected $data = array();

	public static function get($name) {
		if (array_key_exists($name, $array = self::inst()->data))
			return $array[$name];
		else
			throw new DataStoreException('DataStore: key '.$name.' not found');
	}

	public static function set($name, $value) {
		self::inst()->data[$name] = $value;
	}
}

?>