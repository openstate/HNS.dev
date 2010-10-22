<?php

require_once 'routes/RouteInterface.interface.php';
require_once 'Destination.class.php';

/*
	Class DirectRoute
	A route meant for routes that are static during run of the site and are decided during development.
	For example where to map '/'.
	Note: Make sure that for each route you add you also implement the reverse route (function assemble)
*/

class DirectRoute implements RouteInterface {
	
	protected $routes = array(
		'/admin' => array('module' => 'admin', 'controller' => 'index', 'action' => 'index', 'parameters' => array())
	);

	public function match($path) {
		$destination = new Destination();
		$path = rtrim($path, '/');
		if (!$path) $path = '/';
		if(isset($this->routes[$path])) {
			$route = $this->routes[$path];
			foreach($route as $key => $value)
				$destination->$key = $value;
			return $destination;
		}
		return false;
	}
	
	public function assemble(Destination $destination) {
		foreach($this->routes as $path => $route) {
			foreach($route as $key => $value)
				if ($destination->$key != $value)
					continue 2;
			return $path;
		}
		
		return false;
	}
}

?>