<?php

class String {
	protected static function splitUtf8($str) {
		$result = array();
		while (strlen($str)) {
			$ch = ord($str[0]);
			if ($ch >= 0xF0) $length = 4;
			else if ($ch >= 0xE0) $length = 3;
			else if ($ch >= 0xC0) $length = 2;
			else $length = 1;
			$result[] = substr($str, 0, $length);
			$str = substr($str, $length);
		}
		return $result;
	}

	public static function unaccent($text) {
		$from = self::splitUtf8('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ');
		$to =         str_split('AAAAAAACEEEEIIIIDNOOOOOOUUUUYTsaaaaaaaceeeeiiiienoooooouuuuyty');
		return strtr($text, array_combine($from, $to));
	}

	public static function urlize($text) {
		return trim(self::hyphen(self::unaccent($text)), '-');
	}

	public static function hyphen($text) {
		return strtolower(preg_replace('/[^A-Za-z0-9]+/', '-',
		                  preg_replace('/([a-z\d])([A-Z])/', '\1-\2',
		                  preg_replace('/([A-Z]+)([A-Z][a-z])/', '\1-\2', $text))));
	}
}

?>