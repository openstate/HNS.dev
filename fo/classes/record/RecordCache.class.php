<?php

class RecordCache {
	protected static $cache = array();
	protected static $getCount = 0;

	/*
		Not constructable
	*/
	private function __construct() {}

	/*
		Stores the data in the array under a specific classname and primary key
	*/
	public static function add($className, $pk, $data) {
		if (!isset(self::$cache[$className]))
			self::$cache[$className] = array();
		self::$cache[$className][$data[$pk]] = $data;
	}

	/*
		Get the data from the cache by a specific classname and primary key
	*/
	public static function get($className, $pk) {
		if (!isset(self::$cache[$className]))
			self::$cache[$className] = array();

		if (isset(self::$cache[$className][$pk])) {
			self::$getCount++;
			return self::$cache[$className][$pk];
		}
		return false;
	}

	/*
		Find the data in the the cache for a specific class where one of the data's propperties matches a value
	*/
	public static function find($className, $property, $value) {
		if (!isset(self::$cache[$className]))
			return false;

		foreach (self::$cache[$className] as $pk => $data) {
			try {
				if ($data[$property] == $value) {
					self::$getCount++;
					return $data;
				}
			} catch (Exception $e) {
				break;
			}
		}
		return false;
	}

	/*
		Remove data from the cache by specific classname and primary key
	*/
	public static function remove($className, $pk = null) {
		if (!isset(self::$cache[$className]))
			self::$cache[$className] = array();

		if ($pk === null) {
			unset(self::$cache[$className]);
		} else {
			if (isset(self::$cache[$className][$pk]))
				unset(self::$cache[$className][$pk]);
		}
	}

	/*
		Returns the whole cache.
		Shouldn't be used.
	*/
	public static function getCache() {
		return self::$cache;
	}

	/*
		Returns some cache statistics
		Used for debugging
	*/
	public static function cacheStats() {
		echo '<pre>';
		$classCount = 0;
		$instanceCount = 0;
		foreach (self::$cache as $class => $array) {
			$classCount++;
			echo 'Class: ' . $class . "\nIds: ";
			$pkCount = 0;
			foreach ($array as $pk => $data) {
				$pkCount++;
				echo $pk . ',';
			}
			$instanceCount += $pkCount;
			echo "\n".'#pks: ' . $pkCount . "\n";
			echo "----\n";
		}
		echo '#classes: ' . $classCount . "\n";
		echo '#instances: ' . $instanceCount . "\n";
		echo '#gets: ' . self::$getCount . "\n";
		echo '</pre>';
	}
}