<?php
require_once 'exceptions/NoRouteException.class.php';
require_once 'Destination.class.php';
require_once 'Translater.class.php';

abstract class Controller {
	protected $actions = array();
	protected $defaultAction = 'index';
	protected $view = null;
	protected $request;
	protected $response;

	public function __construct() {
		$this->autoPopulateActions();
	}

	protected function autoPopulateActions() {
		//populate the actions, the lazy way
		foreach(get_class_methods($this) as $method) {
			if (substr($method, -6) === 'Action') {
				$this->actions[strtolower(substr($method, 0, -6))] = true;
			}
		}
	}

	/*
		Method setViewTemplatePath
		sets the views templatePath so it knows where to find this modules view.
		Reason this is done in a seperate function is so a controller can overwrite this function in case
		it doesn't use the standard templates directory
	*/
	protected function setViewTemplatePath(Request $request) {
		if($this->view)
			$this->view->setTemplatePath(realpath('../modules/'.$request->getDestination()->module.'/views/').'/');
		else
			throw new Exception('No view set');
	}

	public function dispatch($request, $response) {
		$this->request = $request;
		$this->response = $response;
		$destination = $request->getDestination();

		$this->view = $response->createView();
		$this->view->setTranslater(new Translater($request->getSite()->getLocale(), $destination->module, null));
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales/');
		$this->setViewTemplatePath($request);
		if (!empty($destination->action) && array_key_exists(strtolower($destination->action), $this->actions)) {
			//NO-OP
		} elseif (empty($destination->action) && array_key_exists($this->defaultAction, $this->actions)) {
			$destination->action = $this->defaultAction;
		} else {
			throw new NoRouteException(get_class($this).' couldn\'t route request ' . $response->getViewHelper()->reverseRoute($request->getDestination()->toUrlString()));
		}

		if (method_exists($this, 'preDispatch')) {
			$this->preDispatch();
		}

		$methodName = strtolower($destination->action).'Action';
		$this->$methodName($request, $response);

		if (method_exists($this, 'postDispatch')) {
			$this->postDispatch();
		}
	}

	public function __call($name, $args) {
		if (preg_match('/^.*Action$/ui', $name)) {
			throw new NoRouteException(get_class($this).' couldn\'t route request ' . $this->response->getViewHelper()->reverseRoute($this->request->getDestination()->toUrlString()));
		} else {
			throw new Exception('Call to undefined function: ' . get_class($this) . '::' . $name);
		}
	}


	protected function redirect($internalUrl, $code = 302) {
		$url = $this->route($internalUrl);
		$this->response->redirect($url, $code);
	}

	protected function route($internalUrl) {
		return $this->response->getViewHelper()->reverseRoute($internalUrl);
	}

	protected function addPoFile($file, $path = false) {
		if ($path) {
			$path = rtrim($path, '/');
			$this->view->getEngine()->addPoFile($file, $path);
		} else {
			$this->view->getEngine()->addPoFile($file, $_SERVER['DOCUMENT_ROOT'].'/../modules/'.$this->request->getDestination()->module.'/locales');
		}

	}
}
