<?php

require_once 'Destination.class.php';

class Router {
	
	protected $routes = array();
	protected $useDefaultRoutes = true;
	protected $currentRoute = null;
	protected $domain = null;
	
	public function __construct($domain = null) {
		$this->domain = $domain;
	}

	public function addDefaultRoutes() { // TODO: zou eruit moeten, hoort hier niet

		if (!$this->hasRoute('default')) {
			require_once 'Routes/ModuleRoute.class.php';
			$route = new ModuleRoute();
			$this->routes = array_merge(array('default' => $route), $this->routes);
		}
		return $this;
	}

	public function removeDefaultRoutes() { // TODO: zou eruit moeten, hoort hier niet
		$this->routes = array(); // WTF?? verwijderd alle routes???
		$this->useDefaultRoutes = false;
		return $this;
	}

	public function hasRoute($name) {
		 return isset($this->routes[$name]);
	}

	public function addRoute($name, $route) {
		if ($route instanceof RouteInterface) {
			$this->routes[$name] = $route;
		}
		return $this;
	}

	public function removeRoute($name) {
		if ($this->hasRoute($name)) {
			unset($this->routes[$name]);
		}
		return $this;
	}

	public function getRoute($name) {
		if ($this->hasRoute($name)) {
			return $this->routes[$name];
		}
		return null;
	}

	public function getRoutes() {
		return $this->routes;
	}

	public function getCurrentRoute() {
		return $this->getRoute($this->currentRoute);
	}

	public function getCurrentRouteName() {
		return $this->currentRoute;
	}

	public function routeRequest(Request $request) {
		$path = $request->getPathInfo();
		if (($destination = $this->routeUrl($path)) === false) {		
			throw new NoRouteException('No Route', $request);	
		}
		$request->setDestination($destination);
	}

	public function routeUrl($path) {
		$result = false;		
		foreach (array_reverse($this->routes) as $name => $route) {			
			if (($destination = $route->match($path)) !== false) {				
				$result = $destination;
				$this->currentRoute = $name;
				break;
			}
		}

		if($result === false)
			throw new NoRouteException(__CLASS__.' couldn\'t route request ' . $path);

		return $result;
	}
	
	public function reverseRoute(Destination $destination) {
		foreach(array_reverse($this->routes) as $name => $route) {			
			if (($path = $route->assemble($destination)) !== false) {				
				if ($destination->subdomain && $this->domain && $path[0] == '/')
					$path = 'http://'.$destination->domain.'.'.$this->domain.$path;
				return $path;
			}
		}
		return false;
	}
}