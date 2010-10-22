<?php

require_once 'Module.abstract.php';

class LoggingModule extends Module {
	public function loadBackofficeControllers() {
		$this->addController('logging', dirname(__FILE__) . '/controllers/LoggingLoggingController.class.php');
		$this->addController('versioning', dirname(__FILE__) . '/controllers/LoggingVersioningController.class.php');
		$this->addController('database', dirname(__FILE__) . '/controllers/LoggingDatabaseController.class.php');
	}
	
	public function preDispatch() {		
		if ($this->request->getSite()->getSiteName() == 'backoffice' && !$this->request->isBlock) {
			$layout = $this->response->getLayout();
			$layout->activeNavigation = 'logging';
			$layout->activeApplication = $this->request->getDestination()->controller;
		}
	}
}
