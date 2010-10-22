<?php

class Dispatcher {
	protected $urls = array(
		'!^/query/?+$!' => '/xml/ApiCall.class.php',
		'!^/test/?+$!' => '/system/TestPage.class.php',
		'!^(.+)$!' => '/public_html$1'
	);

	protected static $inst = null;

	public static function inst() {
		$class = __CLASS__;
		if (!self::$inst)
			self::$inst = new $class();
		return self::$inst;
	}

	protected function __construct() { }

	public function dispatch() {
		$url = $_SERVER['SCRIPT_URL'];
		foreach ($this->urls as $pattern => $call) {
			if (preg_match($pattern, $url, $matches)) {
				foreach ($matches as $num => $match) {
					$call = str_replace('$U'.$num, ucfirst($match), $call);
					$call = str_replace('$'.$num, $match, $call);
				}
				$call = realpath($_SERVER['DOCUMENT_ROOT'].'/../'.$call);
				if (!file_exists($call))
					self::error(404);
				if (pathinfo($call, PATHINFO_EXTENSION) != 'php') {
					header('Content-Type: '.mime_content_type($call));
					header('Content-Length: '.filesize($call));
					readfile($call);
					die;
				}
				try {
					require_once($call);
					$class = basename($call, '.class.php');
					if (!class_exists($class, false))
						self::error(404);
					$obj = new $class();
					session_start();
					if ($_POST && is_callable(array($obj, 'processPost')))
						$obj->processPost($_POST);
					if (is_callable(array($obj, 'processGet')))
						$obj->processGet($_GET);
					if (is_callable(array($obj, 'show')))
						$obj->show();
				} catch (Exception $e) {
					if (DEVELOPER) {
						ob_clean();
						header('HTTP/1.1 500 Internal Server Error');
						header('Content-Type: text/html');
						echo('<html><body><h1>500 Internal Server Error</h1></body><pre>'.$e->__toString().'</pre></html>');
						die;
					} else {
						mail('exceptions@accepte.nl', 'HNS-Dev exception', 'Request: '.$_SERVER['REQUEST_URI']."\n".$e->__toString());
						self::error(500);
					}
				}
				break;
			}
		}
	}

	public static function error($code) {
		$codes = require('httpcodes.include.php');
		ob_clean();
		header('HTTP/1.1 '.$code.' '.$codes[$code]);
		header('Content-Type: text/html');
		session_write_close();
		echo('<html><body><h1>'.$code.' '.$codes[$code].'</h1></body></html>');
		die;
	}

	public static function redirect($url) {
		ob_clean();
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: '.$url);
		session_write_close();
		die;
	}

}

?>