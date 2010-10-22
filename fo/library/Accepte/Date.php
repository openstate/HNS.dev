<?php

class Accepte_Date {

	protected $_datetime = null;
	protected static $_locale = 'nl_NL';

	const DATES          = 'DATES';          // --- standard date, locale aware
    const DATE_FULL      = 'DATE_FULL';      // --- full date, locale aware
    const DATE_LONG      = 'DATE_LONG';      // --- long date, locale aware
    const DATE_MEDIUM    = 'DATE_MEDIUM';    // --- medium date, locale aware
    const DATE_SHORT     = 'DATE_SHORT';     // --- short date, locale aware
    const TIMES          = 'TIMES';          // --- standard time, locale aware
    const TIME_FULL      = 'TIME_FULL';      // --- full time, locale aware
    const TIME_LONG      = 'TIME_LONG';      // --- long time, locale aware
    const TIME_MEDIUM    = 'TIME_MEDIUM';    // --- medium time, locale aware
    const TIME_SHORT     = 'TIME_SHORT';     // --- short time, locale aware

	protected static $_conversion = array(
			'dd' => 'd'  , 'EE' => 'D'  , 'EEE' => 'D', 'd' => 'j'   , 'EEEE' => 'l', 'e' => 'N'   , 'SS' => 'S'  ,
			'eee' => 'w' , 'D' => 'z'   , 'w' => 'S'   , 'MMMM' => 'F', 'MM' => 'm'  , 'MMM' => 'M' ,
				'M' => 'n'   , 'ddd' => 't' , 'l' => 'L'   , 'YYYY' => 'o', 'YY' => 'y'  , 'yyyy' => 'o',
			'h' => 'g'   , 'H' => 'G'   , 'hh' => 'h'  , 'HH' => 'H'  , 'mm' => 'i'  , 'ss' => 's'  ,
			'zzzz' => 'e', 'Z' => 'O'   , 'ZZZZ' => 'P', 'z' => 'T'   , 'X' => 'Z'
	);

	public function __construct($value = 'now') {
		if (is_int($value)) { //assume timestamp
			$value = '@'.$value;
		}
				
		$this->_datetime = new DateTime($value);		
	}

	public static function setLocale($locale) {
		self::$_locale = $locale;
	}

	public function format($format = 'dd MM YYY', $locale = false) {
		require_once 'Zend/Locale/Data.php';

		//check format for constants
		$f = $this->_constantFormat($format);
		if ($f !== false) {
			$format = $f;
		}

		$format = self::convertIso($format);

		if (!$locale) {
			$locale = self::$_locale;
		}
		$result = '';
		$values = str_split($format);
		foreach ($values as $value) {
			switch($value) {
				case 'a':
				case 'A':
					$result .= Zend_Locale_Data::getContent($locale, $this->_datetime->format('a'));
					break;

				case 'D': //3 letter day of the week
					$result .= Zend_Locale_Data::getContent($locale, 'day', array('gregorian', 'format', 'abbreviated', strtolower($this->_datetime->format('D'))));
					break;

				case 'l': //full day of the week
					$result .= Zend_Locale_Data::getContent($locale, 'day', array('gregorian', 'format', 'wide', strtolower($this->_datetime->format('D'))));
					break;
				case 'M': //3 letter month
					$result .= Zend_Locale_Data::getContent($locale, 'month', array('gregorian', 'format', 'abbreviated', strtolower($this->_datetime->format('n'))));
					break;

				case 'F': //full month
					$result .= Zend_Locale_Data::getContent($locale, 'month', array('gregorian', 'format', 'wide', strtolower($this->_datetime->format('n'))));
					break;

				default:
					//all non locale aware items are formatted here
					if (in_array(strtolower($value), range('a', 'z'))) {
						$result .= $this->_datetime->format($value);
					} else {
						//everything else is just separators, so add them to the result.
						$result .= $value;
					}
				break;
			}
		}
		return $result;
	}


	public static function convertIso($isoformat) {
		$values = preg_split('/(\W+)/', $isoformat, -1, PREG_SPLIT_DELIM_CAPTURE);		
		$result = '';
		foreach ($values as $value) {
			if (isset(self::$_conversion[$value])) {
				$result .= self::$_conversion[$value];
			} else {
				$result .= $value;
			}
		}
		return $result;
	}

	protected function _constantFormat($format, $locale = false) {
		if (!$locale) {
			$locale = self::$_locale;
		}

		switch($format) {
			case self::DATES:
				return Zend_Locale_Data::getContent($locale, 'date');
			case self::DATE_FULL:
				return Zend_Locale_Data::getContent($locale, 'date', array('gregorian', 'full'));
			case self::DATE_LONG:
				return Zend_Locale_Data::getContent($locale, 'date', array('gregorian', 'long'));
			case self::DATE_MEDIUM:
				return Zend_Locale_Data::getContent($locale, 'date', array('gregorian', 'medium'));
			case self::DATE_SHORT:
				return Zend_Locale_Data::getContent($locale, 'date', array('gregorian', 'short'));
			case self::TIMES:
				return Zend_Locale_Data::getContent($locale, 'time');
			case self::TIME_FULL:
				return Zend_Locale_Data::getContent($locale, 'time', 'full');
			case self::TIME_LONG:
				return Zend_Locale_Data::getContent($locale, 'time', 'long');
			case self::TIME_MEDIUM:
				return Zend_Locale_Data::getContent($locale, 'time', 'medium');
			case self::TIME_SHORT:
				return Zend_Locale_Data::getContent($locale, 'time', 'short');
			default:
				return false;
		}
	}

	public function __call($name, $params) {
		call_user_func_array(array($this->_datetime, $name), $params);
	}
}
