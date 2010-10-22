<?php

require_once 'Module.abstract.php';

class DefaultModule extends Module {
	public function loadControllers() {
		$this->addController('index', dirname(__FILE__) . '/controllers/DefaultIndexController.class.php');
	}	

	public function loadBackofficeControllers() {
	}
} 
