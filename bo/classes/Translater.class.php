<?php

require_once 'view/smarty/GettextPO.class.php';

class Translater {
	protected static $gettext = array();

	const SYSTEM = '__system';

	protected $defaultSource = null;
	protected $defaultModule = null;

	public function __construct($locale, $source = null, $module = null) {
		$this->source = $source;
		$this->module = $module;
		$this->locale = reset(explode('_', $locale));
	}

	public function translate($msgid, $source = null, $module = null, $locale = null) {
		if (!$source && !$module) {
			$source = $this->source;
			$module = $this->module;
		}
		if (!$locale)
			$locale = $this->locale;
		if (!$source)
			return $msgid;
		if (!$module) {
			if (isset(self::$gettext[$locale][$source][$source]))
				$module = $source;
			else if (isset(self::$gettext[$locale][self::SYSTEM][$source]))
				$module = self::SYSTEM;
		}
		if (!$module || !isset(self::$gettext[$locale][$module][$source])) {
			if (!$module || $module != self::SYSTEM) {
				$mod = $module ? $module : $source;
				$file = $_SERVER['DOCUMENT_ROOT'].'/../modules/'.$mod.'/locales/'.$locale.'/'.$source.'.po';
				if (file_exists($file)) {
					$module = $mod;
					self::$gettext[$locale][$module][$source] =  new GettextPO($file);
				}
			}
			if (!$module || $module == self::SYSTEM) {
				$file = $_SERVER['DOCUMENT_ROOT'].'/../locales/'.$locale.'/'.$source.'.po';
				if (file_exists($file)) {
					$module = self::SYSTEM;
					self::$gettext[$locale][$module][$source] =  new GettextPO($file);
				}
			}
			if (!$module)
				return $msgid;
		}
		$result = self::$gettext[$locale][$module][$source]->getMsgStr($msgid);
		return $result === false ? $msgid : $result;
	}

}

?>