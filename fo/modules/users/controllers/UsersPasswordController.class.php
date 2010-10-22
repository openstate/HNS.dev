<?php

require_once 'Controller.abstract.php';

require_once 'User.class.php';
require_once 'UserPasswordRequest.class.php';

require_once '../formgen/formgen/core/FormInstance.class.php';


class UsersPasswordController extends Controller {
	
	public function indexAction() {
		if ($this->request->user && $this->request->user->loggedIn) {
			$this->response->redirect('/');
			return;
		}	
	
		$form = new FormInstance(dirname(__FILE__).'/../forms/password/password.form');
		$form->addCallback('isValidEmail', array($this, 'isValidEmail'));

		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPOST(), $this->request->getFILES());
			if ($form->isValid()) {
				$values = $form->getValues();
				$user = new User();
				$user->loadByUnique($values['email']);
				$hash = $user->requestNewPassword();

				require_once 'Accepte/Mail.php';
				$mail = new Accepte_Mail($this->response->createView('Smarty'));
				$mail->setBaseUrl('http://'.$this->request->getSite()->getFullDomain());
				$mail->addTo($user->email);
				$mail->setSubject('Je wachtwoord aanvraag');

				$mail->setBodyText('Er is een wachtwoord aanvraag gedaan voor je account.'."\n".
					'Om dit verzoek af te ronden klik je op onderstaande link.'."\n".
					'Als jij geen nieuw wachtwoord opgevraagt hebt doe dan verder niks.'."\n".
					'http://www.'.$this->request->getSite()->getTopDomain().$this->response->route('/users/password/reset/?hash='.$hash)
				);

				$mail->setBodyHtml('<h3>Er is een wachtwoord aanvraag gedaan voor je account.</h3>'.
					'<p>Om dit verzoek af te ronden klik je op onderstaande link.</p>'.
					'<p>Als jij geen nieuw wachtwoord opgevraagt hebt doe dan verder niks.</p>'.
					'<p><a href="http://www.'.$this->request->getSite()->getTopDomain().$this->response->route('/users/password/reset/?hash='.$hash).'" style="font-weight:bold;">Stuur mij een nieuw wachtwoord</a></p>'
				);

				$mail->send();

				return $this->redirect('/users/password/requestsend/');
			}
		}

		$this->view->form = $form;
		$this->view->render('password/index.html');
	}

	public function requestSendAction() {
		$this->view->render('password/requestSend.html');
	}

	public function resetAction() {
		$hash = $this->request->getNamedParam('hash');

		if (empty($hash) || !preg_match('/^[a-zA-Z0-9]{40}$/', $hash)) {
			$this->response->redirect('/');
		}

		$user = new User();
		$password = $user->confirmNewPassword($hash);

		if ($password === false) {
			$this->response->redirect('/');
		}

		require_once 'Accepte/Mail.php';
		$mail = new Accepte_Mail($this->response->createView('Smarty'));
		$mail->setBaseUrl('http://'.$this->request->getSite()->getFullDomain());
		$mail->addTo($user->email);
		$mail->setSubject('Je nieuwe wachtwoord');
		$mail->setBodyText('Je nieuwe wachtwoord is: ' . $password);

		$mail->setBodyHtml('<h3>Je nieuwe wachtwoord</h3>'.
			'<p>Je nieuwe wachtwoord is: <span style="font-weight: bold;">'.$password.'</span></p>'
		);
		$mail->send();

		return $this->redirect('/users/password/passwordsend/');
	}

	public function passwordSendAction() {
		$this->view->render('password/passwordSend.html');
	}

	public function isValidEmail($values) {		
		$user = new User();
		try {
			if ($user->loadByUnique('email', strtolower($values['email']))) {				
				return true;
			}
		} catch (RecordNotFoundException $e) {}
		return false;
	}
}