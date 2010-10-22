<?php

require_once 'Module.abstract.php';

class ProjectsModule extends Module {
  	public function loadControllers() {
		$this->addController('index', dirname(__FILE__) . '/controllers/ProjectsIndexController.class.php');
	}	
} 
