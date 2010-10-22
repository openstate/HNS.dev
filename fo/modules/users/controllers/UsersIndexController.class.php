<?php

require_once 'Controller.abstract.php';

require_once '../formgen/formgen/core/FormInstance.class.php';
require_once('UserExtended.class.php');

class UsersIndexController extends Controller {
	protected $defaultAction = 'login';

/*	public function loginAction() {
		$destination = $this->request->getNamedParam('destination', '/admin/');
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
		$destination = $this->request->getNamedParam('destination', '/admin/');
		if ($this->request->user) {
			$this->request->user->removeCookie($this->request->getSite()->getTopDomain());
			$this->request->user->logout();
			$this->response->redirect($destination);
		}
	}

	public function isCorrectLogin($values) {
		return $this->request->user->login($values['email'], $values['password'], $values['cookie']);
	}
*/
	public function saveUser($id, $values) {
		$user = new UserExtended();
		if ($id) 
			$user->load($id);
		else 
			$user->user_id = $this->request->user->getPk();
				
		if ($this->request->getPost('clear_photo', false))
			$user->photo->delete();
		elseif ($values['photo']['error'] != UPLOAD_ERR_NO_FILE)
			$user->photo = $values['photo'];

		$user->organization = $values['organization'];
		$user->postalcode = $values['postalcode'];
		$user->phone = $values['phone'];
		$user->twitter = $values['twitter'];
		$user->skype = $values['skype'];
		$user->shortbio = $values['shortbio'];
		if (!$id) {
			$user->rights_read = 1;
			$user->rights_write = 0;
		}
		
		$user->save();
		
		require_once('Wiki.class.php');
		Wiki::inst()->edit($user->getWikiTitle(), $user->getWikiContent());

		return $user->getPk();
	}
	
	public function loadUser($id) {
		$user = new UserExtended();
		$user->load($id);
		
		return array(
			'user_real_name' => $user->user->user_real_name, 
			'user_email' => $user->user->user_email, 
			'photo' => $user->photo->getLink(),
			'organization' => $user->organization,
			'postalcode' => $user->postalcode,
			'phone' => $user->phone,
			'twitter' => $user->twitter,
			'skype' => $user->skype,
			'shortbio' => $user->shortbio,
		);
	}

	public function saveCredentials($id, $values) {
		$user = new UserExtended();
		$user->load($id);
		
		$user->rights_read = (int) @$values['read'];
		$user->rights_write = (int) @$values['write'];
		
		$user->save();
	}

	public function loadCredentials($id) {
		$user = new UserExtended();
		$user->load($id);
		
		return array(
			'read' => $user->rights_read,
			'write' => $user->rights_write,
		);
	}

	public function credentialsAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
		$form = new FormInstance(dirname(__FILE__).'/../forms/index/credentials.form');

		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		
		$user = new UserExtended();
		try {
			$user->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}
		
		if ($this->request->user->user_id != $user->user_id) {
			$this->displayLogin();
			return;
		}
	
		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$this->saveCredentials($id, $form->getValues());
				$this->redirect('/users/index/change/'.$id);
			}
		} else {
			$form->setRawdata($this->loadCredentials($id));
		}

		$this->view->form = $form;
		$this->addPoFile('users.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->view->render('index/user.html');
	}
	
	public function createAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
		$user = new user();
		$user->load($this->request->user->getPk());
		
		$form = new FormInstance(dirname(__FILE__).'/../forms/index/edit.form');
		$form->addCallback('isValidImage', array($this, 'isValidImage'));
		
		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$id = $this->saveUser(false, $form->getValues());
				$this->redirect('/users/index/credentials/'.$id);
			}
		} else {
			$form->setRawData($user->toArray());
		}

		$this->view->form = $form;
		$this->addPoFile('users.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->view->render('index/user.html');
	}
	
	public function editAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
		$form = new FormInstance(dirname(__FILE__).'/../forms/index/edit.form');
		$form->addCallback('isValidImage', array($this, 'isValidImage'));
		
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		
		$user = new UserExtended();
		try {
			$user->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}
		
		if ($this->request->user->user_id != $user->user_id) {
			$this->displayLogin();
			return;
		}
		
		$this->checkUser = $user;
	
		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$this->saveUser($id, $form->getValues());
				$this->redirect('/users/index/change/'.$id);
			}
		} else {
			$form->setRawdata($this->loadUser($id));
		}

		$this->view->form = $form;
		$this->view->has_photo = (boolean) $user->photo->value;
		$this->addPoFile('users.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->view->render('index/user.html');
	}

	public function changeAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$user = new UserExtended();
		try {
			$user->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}

		if ($this->request->user->user_id != $user->user_id) {
			$this->displayLogin();
			return;
		}
	
		$this->view->id = $id;
		$this->view->user = $user;
		$this->addPoFile('users.po');
		$this->view->render('index/change.html');
	}
	
	public function viewAction() {
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$user = new UserExtended();
		try {
			$user->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}

		require_once('Wiki.class.php');
		$this->response->redirect(Wiki::inst()->redirect($user->getWikiTitle()));
		return;

		if ($this->request->user->user_id != $user->user_id) {
			$this->displayLogin();
			return;
		}
	
		$this->view->id = $id;
		$this->view->user = $user;
		$this->addPoFile('users.po');
		$this->view->render('index/view.html');
	}
	
	public function isValidImage($values) {
		require_once('record/objects/ImageObject.class.php');
		$value = reset($values);
		$obj = new ImageObject(null, null, array());
		return $obj->checkValue($value);
	}
	
}