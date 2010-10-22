<?php

require_once 'routes/RouteInterface.interface.php';
require_once 'Destination.class.php';

class ModuleRoute implements RouteInterface {
	protected $prefix = '';

	public function __construct($prefix = 'modules') {
		$this->prefix = $prefix;
	}

	public function match($path) {	
		$path = trim($path, '/');

		if ($this->prefix !== '' && !substr($path, 0, strlen($this->prefix)+1) === $this->prefix.'/')
			return false;
		
		$path = trim(substr($path, strlen($this->prefix))), '/');
		$parts = explode('/', $path);
		$destination = new Destination();
		
		// module
		if (count($parts) && !empty($parts[0])) {
			$destination->module = array_shift($parts);
		}

		// check module
		if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/../modules/'.$destination->module))
			return false;

		// controller
		if (count($parts)) {
			$part = array_shift($parts);
			if (!empty($part))
				$destination->controller = $part;
		}
		
		// action
		if (count($parts)) {
			$part = array_shift($parts);
			if (!empty($part))
				$destination->action = $part;
		}
		$destination->parameters = $parts;
		return $destination;
	}
	
	public function assemble(Destination $destination) {
		$url = array();		
		if (count($destination->parameters)) {
			$url[] = $destination->module;
			$url[] = $destination->controller;
			$url[] = $destination->action;
			$url = array_merge($url, $destination->parameters);
		} else {
			$url[] = $destination->module;
			if ($destination->controller !== null) {
				$url[] = $destination->controller;
				if ($destination->action !== null) {
					$url[] = $destination->action;
				}
			}
		}		
		
		return '/'.implode('/', $url);
	}
}

?>