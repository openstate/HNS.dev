<?php

require_once 'Module.abstract.php';

class AffiliatesModule extends Module {
	public function loadBackofficeControllers() {
		$this->addController('index', dirname(__FILE__) . '/controllers/AffiliatesIndexController.class.php');
	}

	public function preDispatch() {		
		if ($this->request->getSite()->getSiteName() == 'backoffice' && !$this->request->isBlock) {
			$layout = $this->response->getLayout();
			$layout->activeNavigation = 'users';
			$layout->activeApplication = 'affiliates';
		}
	}

} 
