<?php

/*
	Class Destination
	Represents a destination within the system made up from a module and a controller. This is returned by the router to the dispatcher
*/

class Destination {
	
	public $subdomain = null;
	public $module = null;
	public $controller = null;
	public $action = null;
	public $parameters = array();
	public $cmsParameters = array();
	
	/*
		Sets the properties of the destination from the given url string
	*/
	public function fromUrlString($urlString) {
		$parts = explode(':/', $urlString, 2);
		if (count($parts) > 1) {
			$this->subdomain = $parts[0];
			$urlString = '/' . $parts[1];
		}
		$path = explode('/', trim($parts[0], '/'));
		if ($path) $this->module = array_shift($path);
		if ($path) $this->controller = array_shift($path);
		if ($path) $this->action = array_shift($path);
		while ($path) array_unshift($this->parameters, array_pop($path));
	}
	
	/*
		Converts the destination object to a internal url
	*/
	public function toUrlString() {
		$result = '';
		if ($this->subdomain)
			$result .= $this->subdomain.':';
		$result .= '/';
		$result .= $this->module .'/';
		if ($this->controller) {
			$result .= $this->controller .'/';
			if ($this->action) {
				$result .= $this->action .'/';
			}
			$result .= implode('/', $this->parameters);
		}
		return $result;
	}

}
