<?php

require_once 'Controller.abstract.php';

require_once $_SERVER['DOCUMENT_ROOT'].'/../formgen/formgen/core/FormInstance.class.php';

class UsersIndexController extends Controller {
	protected $defaultAction = 'login';

	public function loginAction() {
		$destination = $this->request->getNamedParam('destination', '/');
		$d = new Destination();
		$d->fromUrlString($destination);
		if ($d->toUrlString(true) == '/users/index/login/') {
			$destination = '/';
		}
		$site = $this->request->getSite()->getSiteName();
		$form = new FormInstance(dirname(__FILE__).'/../forms/'.($site == 'backoffice' ? 'admin' : 'index').'/login.form');
		$form->addCallback('isCorrectLogin', array($this, 'isCorrectLogin'));

		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPOST(), $this->request->getFILES());
			if ($form->isValid()) {
				$values = $form->getValues();
				$user = new User();
				if ($user->login($values['email'], $values['password'])) {
					if ($values['cookie'])
						$user->setCookie($this->request->getSite()->getTopDomain());
					$request->user = $user;
					$this->response->redirect($destination);
					return;
				}
			}
		} else {
			$form->setRawData(array(
				'destination' => $destination,
			));
		}

		if ($site == 'backoffice')
			$this->response->getLayout()->setTemplate('backofficeLogin.html');
		$this->view->form = $form;
		$this->view->render('index/login.html');
	}

	public function logoutAction() {
		$destination = $this->request->getNamedParam('destination', '/');
		if ($this->request->user) {
			$this->request->user->removeCookie($this->request->getSite()->getTopDomain());
			$this->request->user->logout();
			$this->response->redirect($destination);
		}
	}

	public function isCorrectLogin($values) {
		return $this->request->user->login($values['email'], $values['password'], $values['cookie']);
	}

}