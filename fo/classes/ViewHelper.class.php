<?php

require_once 'Destination.class.php';
require_once 'Dispatcher.class.php';

/**
* ViewHelper class
* Used for reverse routing and display blocks only !
* Do NOT use this class as a hackjob to get to information from the dispatcher !!
*/
class ViewHelper {
	
	protected $dispatcher;
	
	public function __construct(Dispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
	}
	
	public function reverseRoute($internalUrl) {
		list($destination, $getString) = $this->parseUrl($internalUrl);
		
		$result = $this->dispatcher->getRouter()->reverseRoute($destination);
		
		if (!$result || !isset($getString)) return $result;
		
		parse_str($getString, $vars);		
		return $result . '?' . http_build_query($vars);
	}	
		
	public function displayBlock($internalUrl, $blockGet) {
		if ($internalUrl instanceof Destination) {
			var_dump($internalUrl); die;
		}
		list($destination, $get) = $this->parseUrl($internalUrl);
		parse_str($get, $getVars);
		$getVars += $blockGet;		
		return $this->dispatcher->dispatchBlock($destination, $getVars);
	}
	
	protected function parseUrl($internalUrl) {
		if (strpos($internalUrl, '?') !== false) {
			list($url, $getString) = explode('?', $internalUrl, 2);
		} else {
			$url = $internalUrl;
			$getString = null;
		}
		$destination = new Destination();
		$destination->fromUrlString($url);
		return array($destination, $getString);
	}	
}
