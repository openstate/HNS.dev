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
					self::error(404, new Exception($call.' not found'));
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
						self::error(404, new Exception($call.'.class.php not found'));
					$obj = new $class();
					session_start();
					if ($_POST && is_callable(array($obj, 'processPost')))
						$obj->processPost($_POST);
					if (is_callable(array($obj, 'processGet')))
						$obj->processGet($_GET);
					if (is_callable(array($obj, 'show')))
						$obj->show();
				} catch (Exception $e) {
					$type = get_class($e);

					switch($type){
						case 'DatabaseQueryException':
							self::error(400, $e);
							break;
						case 'RecordException':
							self::error(400, $e);
							break;
						case 'RecordNotFoundException':
							self::error(404, $e);
							break;
						case 'ParseException':
							self::error(400, $e);
							break;
						case 'RightsException':
							self::error(403, $e);
							break;
						default:
							self::error(500, $e);
					}
				}
				break;
			}
		}
	}

	public static function error($code, $exception) {
		$codes = require('httpcodes.include.php');
		$xml   = new SimpleXmlElement('<?xml version="1.0" encoding="UTF-8" ?><error></error>');
		$xml->addChild('code', $code);
		$xml->addChild('description', $codes[$code]);
		$xml->addChild('message', $exception->getMessage());

		if (DEVELOPER) {
			$xml->addChild('class', get_class($exception));

			$stack = $xml->addChild('stack');
			foreach($exception->getTrace() as $call){
				$callElement = $stack->addChild('call');

				$function = '';
				if(isset($call['class'])) $function .= $call['class'];
				if(isset($call['type']))  $function .= $call['type'];
				$function .= $call['function'];
				$callElement->addChild('function', $function);

				$location =  '';
				$location .= $call['file'];
				$location .= ':';
				$location .= $call['line'];
				$callElement->addChild('location', $location);
			}

			if (is_a($exception, 'DatabaseQueryException')) {
				$xml->addChild('sql', $exception->getSql());
				$xml->addChild('dberror', $exception->getError());
			}
		}
		else if($code == 500) {
			mail('exceptions@accepte.nl', 'HNS-Dev exception', 'Request: '.$_SERVER['REQUEST_URI']."\n".$exception->__toString());
		}

		header("HTTP/1.0 {$code} {$codes[$code]}");
		header('Content-Type: text/xml');
		echo (str_replace('><', ">\n<", $xml->asXML()));
		exit;
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