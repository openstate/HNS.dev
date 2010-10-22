<?php

require_once 'Controller.abstract.php';

class AdminIndexController extends Controller {

	public function indexAction() {
		$this->view->render('index/index.html');
	}

	public function setLanguageAction() {
		$return = $this->request->getGet('return', '/');
		$locale = $this->request->getGet('locale', 'en_EN');
		$this->request->getSite()->setLocale($locale);

		$this->response->redirect($return);
	}

	public function fileManagerAction() {
		$layout = $this->response->getLayout();
		$layout->activeNavigation = 'filemanager';
		$this->view->render('index/filemanager.html');
	}

}

?>