<?php

require_once 'Module.abstract.php';

class AdminModule extends Module {

	public function loadBackofficeControllers() {
		$this->addController('index',     dirname(__FILE__) . '/controllers/AdminIndexController.class.php');
		$this->addController('system',     dirname(__FILE__) . '/controllers/AdminSystemController.class.php');
		$this->addController('interface',     dirname(__FILE__) . '/controllers/AdminInterfaceController.class.php');		
	}

}
