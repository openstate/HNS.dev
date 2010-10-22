<?php

require_once 'Router.class.php';
require_once 'exceptions/NoRouteException.class.php';
require_once 'exceptions/ForbiddenException.class.php';
require_once 'exceptions/BadRequestException.class.php';
require_once 'plugin/PluginBroker.class.php';

class Dispatcher {
	protected $router = null;
	protected $request = null;
	protected $response = null;
	protected $modulePath;
	protected $modules = array();
	protected $plugins = null;

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Harro
	 **/
	public function __construct($modulePath, Request $request, Response $response) {
		$this->plugins = new PluginBroker();

		$this->setRequest($request);
		$this->setResponse($response);

		$this->modulePath = $modulePath;

		$this->loadModules();
	}

	/**
	 * Loads the modules into memory
	 *
	 * @return void
	 * @author Harro
	 **/
	protected function loadModules() {
		$pattern = realpath($this->modulePath ) . '/*';
		foreach (glob($pattern) as $path) {
			if (is_dir($path)) {
				$moduleName = ucfirst(basename($path));
				$className = $moduleName . 'Module';
				$fileName = $path . '/' . $className . '.class.php';
				if (file_exists($fileName)) {
					require_once $fileName;
					$this->modules[strtolower($moduleName)] = new $className($this->request->getSite()->getSiteName());
				}
			}
		}
	}

	/**
	 * Dispatch to the given destination with the given get variables
	 *
	 * @return String	The result
	 * @author Harro
	 **/
	public function dispatchBlock(Destination $blockDestination, $get) {
		$newRequest = clone $this->request;
		$newRequest->setDestination($blockDestination);
		$newRequest->setGET($get);
		$newRequest->setPOST(array());
		$newRequest->setFILES(array());
		$newRequest->isBlock = true;

		//TODO: Redirects afvangen, een block mag dit nooit doen (kan redirect loops veroorzaken)
		$obLevel   = ob_get_level();
		try {
			ob_start();
			$this->plugins->dispatchLoopStartup($newRequest, $this->response);
			do {
				$newRequest->setDispatched(true);
				$this->plugins->preDispatch($newRequest, $this->response);
				$destination = $newRequest->getDestination();
				$this->modules[$destination->module]->dispatch($newRequest, $this->response);
				$this->plugins->postDispatch($newRequest, $this->response);
			} while(!$newRequest->isDispatched());
			$this->plugins->dispatchLoopShutdown($newRequest, $this->response);
		} catch(NoRouteException $e) {
			ob_get_clean(); //clean up old stuff
			$this->plugins->exception($newRequest, $this->response, $e);
			if (DEVELOPER) {
				return $e->getMessage();
			}
			return '';
		} catch(ForbiddenException $e) {
			ob_get_clean(); //clean up old stuff
			$this->plugins->exception($newRequest, $this->response, $e);
			if (DEVELOPER) {
				return $e->getMessage();
			}
			return '';
		} catch(Exception $e) {
			ob_get_clean(); //clean up old stuff
			$this->plugins->exception($newRequest, $this->response, $e);
			throw $e;
		}
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * This is the normal dispatch process, it routes the request and
	 * then dispatches to the specified location.
	 * Afterwards it catches all the output and sets it as the layout content variable.
	 * Also handles all exceptions
	 *
	 * @return void
	 * @author Harro
	 **/
	public function dispatch() {
		$obLevel = ob_get_level();
		try {
			$router = $this->getRouter();
			$this->plugins->routeStartup($this->request, $this->response);
			$router->routeRequest($this->getRequest());
			$this->plugins->routeShutdown($this->request, $this->response);
			$destination = $this->request->getDestination();
			ob_start();
			$this->plugins->dispatchLoopStartup($this->request, $this->response);
			do {
				$this->request->setDispatched(true);
				$destination = $this->request->getDestination();

				if (!array_key_exists($destination->module, $this->modules)) {
					throw new NoRouteException(get_class($this).' couldn\'t route request ' . $this->request->getRequestUri());
				}
				$this->plugins->preDispatch($this->request, $this->response);
				$this->modules[$destination->module]->dispatch($this->request, $this->response);
				$this->plugins->postDispatch($this->request, $this->response);
			} while(!$this->request->isDispatched());
			$this->plugins->dispatchLoopShutdown($this->request, $this->response);
		} catch(NoRouteException $e) {
			while (ob_get_level() > $obLevel)
				ob_get_clean();
			$this->plugins->exception($this->request, $this->response, $e);
			$this->response->setHttpResponseCode(404);
			$view = $this->response->createView();
			$view->setTemplatePath($_SERVER['DOCUMENT_ROOT'] . '/../templates/');
			$view->message = $e->getMessage();
			$view->requestUri = $this->request->getRequestUri();
			$view->returnUrl = $e->getReturnUrl();
			$view->render('errors/404.html');
		} catch (ForbiddenException $e) {
			while (ob_get_level() > $obLevel)
				ob_get_clean();
			$this->plugins->exception($this->request, $this->response, $e);
			$this->response->setHttpResponseCode(403);
			$view = $this->response->createView();
			$view->setTemplatePath($_SERVER['DOCUMENT_ROOT'] . '/../templates/');
			$view->message = $e->getMessage();
			$view->requestUri = $this->request->getRequestUri();
			$view->render('errors/403.html');
		} catch (BadRequestException $e) {
			while (ob_get_level() > $obLevel)
				ob_get_clean();
			$this->plugins->exception($this->request, $this->response, $e);
			$this->response->setHttpResponseCode(400);
			$view = $this->response->createView();
			$view->setTemplatePath($_SERVER['DOCUMENT_ROOT'] . '/../templates/');
			$view->message = $e->getMessage();
			$view->requestUri = $this->request->getRequestUri();
			$view->render('errors/400.html');
		} catch (Exception $e) {
			// Clean output buffer on error
			while (ob_get_level() > $obLevel)
				ob_get_clean();
			$this->plugins->exception($this->request, $this->response, $e);
			$data = array(
				'message' =>   $e->getMessage(),
				'file' =>      $e->getFile(),
				'line' =>      $e->getLine(),
				'trace' =>     $e->getTrace(),
				'data' =>      $e instanceof DataException ? $e->getData() : false,
				'exception' => get_class($e),
				'developer' => DEVELOPER

			);

			if ($e instanceof DatabaseQueryException) {
				// Colorize SQL
				$data['sql'] =
					preg_replace('/\b(AS|LEFT|RIGHT|INNER|OUTER|JOIN|ON|TO|AND|OR|ASC|DESC)\b/i', '<span style="color:#00F">$1</span>',
					preg_replace('/\b(SELECT|INSERT|IGNORE|INTO|VALUES|UPDATE|SET|DELETE|REPLACE|RENAME|ALTER|TABLE|TRUNCATE|USE|USING|FROM|WHERE|GROUP|ORDER|BY|LIMIT|UNION)\b/i', '<span style="color:#008000">$1</span>',
					$e->getSQL()));
				$data['error'] = $e->getError();
			}

			$view = $this->response->createView();
			$view->data = $data;
			if (!DEVELOPER) {
				$view->data = $data;
				require_once 'Accepte/Mail.php';
				$mail = new Accepte_Mail($view);
				$mail->setTemplate( $_SERVER['DOCUMENT_ROOT'] . '/../templates/email/exceptions.html');
				$mail->addTo('exceptions@accepte.nl', 'exceptions');
				$mail->setSubject('Exception @ ' . $this->request->getSite()->getTopDomain());
				$mail->setBodyHtml('');
				$mail->setBodyText('Zie de HTML versie');
				$mail->send();
			}
			$view->setTemplatePath($_SERVER['DOCUMENT_ROOT'] . '/../templates/');
			$view->render('exceptions.html');
		}

		$content = ob_get_clean(); //catches all the echo's and whatever.
		$layout = $this->response->getLayout();
		$layout->setContent($content);
		ob_start();
		$layout->render();
		$this->response->appendBody(ob_get_clean());
		$this->response->sendResponse();
	}

	protected function setRequest(Request $request) {
		$this->request = $request;
	}

	public function getRequest() {
		return $this->request;
	}

	protected function setResponse(Response $response) {
		$this->response = $response;
	}

	public function getResponse() {
		return $this->response;
	}

	public function setRouter($router) {
		$this->router = $router;
	}

	public function getRouter() {
		if ($this->router === null)
			$this->setRouter();
		return $this->router;
	}

	public function registerPlugin(Plugin $plugin, $stackIndex = null) {
		$this->plugins->registerPlugin($plugin, $stackIndex);
	}

	public function unregisterPlugin($plugin) {
		$this->plugins->unregisterPlugin($plugin);
		return $this;
	}

	public function hasPlugin($class) {
		return $this->plugins->hasPlugin($class);
	}

	public function getPlugin($class) {
		return $this->plugins->getPlugin($class);
	}

	public function getPlugins() {
		return $this->plugins->getPlugins();
	}
}