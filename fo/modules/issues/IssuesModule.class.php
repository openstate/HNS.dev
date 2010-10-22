<?php

require_once 'Module.abstract.php';

class IssuesModule extends Module {
  	public function loadControllers() {
		$this->addController('index', dirname(__FILE__) . '/controllers/IssuesIndexController.class.php');
	}	
} 
