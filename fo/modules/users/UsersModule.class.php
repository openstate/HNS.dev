<?php

require_once 'Module.abstract.php';

class UsersModule extends Module {
	public function loadControllers() {
		//$this->addController('index', dirname(__FILE__) . '/controllers/UsersIndexController.class.php');
		//$this->addController('password', dirname(__FILE__) . '/controllers/UsersPasswordController.class.php');
		//$this->addController('activation', dirname(__FILE__) . '/controllers/UsersActivationController.class.php');
	}	

	public function loadBackofficeControllers() {
		//$this->addController('list', dirname(__FILE__) . '/controllers/admin/UsersIndexController.class.php');
	}

	public function preDispatch() {		
		if ($this->request->getSite()->getSiteName() == 'backoffice' && !$this->request->isBlock) {
			$layout = $this->response->getLayout();
			$layout->activeNavigation = 'users';
		}
	}

} 
