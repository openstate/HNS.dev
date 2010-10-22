<?php

require_once 'Controller.abstract.php';
require_once 'User.class.php';
require_once 'UserActivation.class.php';

class UsersActivationController extends Controller {
	public function confirmAction() {
		$hash = $this->request->getNamedParam('hash');
				
		$user = new User();
		if (!$user->activateAccount($hash)) {
			$this->response->redirect('/');
		}
		$this->view->render('activation/confirm.html');
	}
	
	//TODO: deactivate account, resend activation email etc.
}