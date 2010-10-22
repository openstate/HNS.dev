<?php

/*
	This will hold some accepte only functions to maintain the website
*/
require_once 'Controller.abstract.php';

class AdminSystemController extends Controller {
	
	public function indexAction() {		
		$this->view->render('system/index.html');
	}
	
	public function phpinfoAction() {
		echo phpinfo();
		die;
	}
}

?>