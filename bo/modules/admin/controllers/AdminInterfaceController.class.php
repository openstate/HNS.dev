<?php

require_once 'Controller.abstract.php';
require_once 'DBs.class.php';

class AdminInterfaceController extends Controller {
	
	public function preDispatch() {
		$this->view->isAdmin = $this->request->user->admin;
	}

	public function tabsAction() {
		$tabs = $this->request->getGet('tabs', array());
		$active = $this->request->getGET('active', false);
		$options = $this->request->getGET('options', false);

		$this->view->tabs = $tabs;
		$this->view->active = $active;
		$this->view->options = $options;
		$this->view->render('interface/tabs.html');
	}

	public function toolbarAction() {
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->view->toolbar = $this->request->getGet('toolbar');
		$this->view->render('interface/toolbar.html');
	}

	public function navigationAction() {
		$this->view->admin = $this->request->user->admin;
		$this->view->accepte_user = $this->request->user->accepte_user;
		$this->view->active = $this->request->getGet('active', false);
		$this->view->render('interface/navigation.html');
	}

	public function applicationsAction() {
		$this->view->admin = $this->request->user->admin;
		$this->view->accepte_user = $this->request->user->accepte_user;			
		$this->view->active = $this->request->getGet('active', false);
		$this->view->section = $this->request->getGet('section', false);
		$this->view->render('interface/applications.html');
	}
}

?>