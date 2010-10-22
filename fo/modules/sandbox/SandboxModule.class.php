<?php

require_once 'Module.abstract.php';

class SandboxModule extends Module {
  	public function loadControllers() {
		$this->addController('index', dirname(__FILE__) . '/controllers/SandboxIndexController.class.php');
	}	
} 
