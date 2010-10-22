<?php

abstract class Module {
	
	protected $defaultController = 'index';
	protected $controllers = array();
	protected $blocks = array();
	protected $name;
	protected $request = null;
	protected $response = null;
	
	final public function __construct($site = null) {
		$this->name = substr(get_class($this), 0, -strlen('Module'));		
		if (method_exists($this, 'loadControllers')) {
			$this->loadControllers();
		}
		if ($site) {
			$function = 'load'.ucfirst($site).'Controllers';
			if (method_exists($this, $function)) {
				$this->$function();
			}
		}
		
		set_include_path(
			$_SERVER['DOCUMENT_ROOT'].'/../modules/'.strtolower($this->name).'/records/'.PATH_SEPARATOR. 
			$_SERVER['DOCUMENT_ROOT'].'/../modules/'.strtolower($this->name).'/plugins/'.PATH_SEPARATOR. 
			get_include_path()
		);
	}
	
	protected function addController($name, $path) {
		$this->controllers[strtolower($name)] = $path;
		return $this;
	}
	
	public function getController($controller) {
		$controller = strtolower($controller);
		$result = null;
		if (isset($this->controllers[$controller])) {
			$fileName = $this->controllers[$controller];
			$className = reset(explode('.', basename($fileName)));
			if (!class_exists($className, false)) {
				require_once $fileName;
			}
			$result = new $className();
		}

		return $result;
	}

	public function getControllers() {
		//TODO: change this instantiated controllers?
		return $this->controllers;
	}
	
	public function dispatch($request, $response) {	
		$this->request = $request;
		$this->response = $response;
		$destination = $request->getDestination();
		if (!empty($destination->controller) && array_key_exists($destination->controller, $this->controllers)) {
			//NO-OP
		} else if (empty($destination->controller) && array_key_exists($this->defaultController, $this->controllers)) {			
			$destination->controller = $this->defaultController;
		} else {			
			throw new NoRouteException(get_class($this).' couldn\'t route request ' . $response->getViewHelper()->reverseRoute($request->getDestination()->toUrlString()));
		}		
		
		if (method_exists($this, 'preDispatch')) {
			$this->preDispatch();
		}

		$controller = $this->getController($this->request->getDestination()->controller);
		$controller->dispatch($request, $response);
		
		if (method_exists($this, 'postDispatch')) {
			$this->postDispatch();
		}
	}
}

?>