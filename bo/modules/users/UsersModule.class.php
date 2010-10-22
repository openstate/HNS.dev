<?php

require_once 'Module.abstract.php';

class UsersModule extends Module {

  	public function loadControllers() {
		$this->addController('index', dirname(__FILE__) . '/controllers/UsersIndexController.class.php');
	}	

	public function loadBackofficeControllers() {
		$this->addController('list', dirname(__FILE__) . '/controllers/admin/UsersIndexController.class.php');
		$this->addController('affiliates', dirname(__FILE__) . '/controllers/admin/UsersAffiliatesController.class.php');
	}

	public function preDispatch() {		
		if ($this->request->getSite()->getSiteName() == 'backoffice' && !$this->request->isBlock) {
			$layout = $this->response->getLayout();
			$layout->activeNavigation = 'users';
			$layout->activeApplication = $this->request->getDestination()->controller;
		}
	}

} 
