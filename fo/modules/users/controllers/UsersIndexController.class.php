<?php

require_once 'Controller.abstract.php';

require_once '../formgen/formgen/core/FormInstance.class.php';
require_once('UserExtended.class.php');

class UsersIndexController extends Controller {
	protected $defaultAction = 'login';

	protected function getTitle($request = null) {
		$request = $request ? $request : $this->request;
		$user = $request->user;
		$title = parent::getTitle($request);
		return sprintf($title, htmlspecialchars($user->user_name));
	}


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
		$user->position = $values['position'];
		$user->postalcode = $values['postalcode'];
		$user->phone = $values['phone'];
		$user->twitter = $values['twitter'];
		$user->linkedin = $values['linkedin'];
		$user->skype = $values['skype'];
		$user->shortbio = $values['shortbio'];
		$user->accept_terms = $values['accept_terms'];
		if (!$id) {
			$user->rights_read = 1;
			$user->rights_write = 0;
		}
		
		$user->save();
		
		require_once('Wiki.class.php');
		Wiki::inst()->edit($user->getWikiTitle().'/Profile', $user->getWikiContent());
		
		if (!$id) {
			Wiki::inst()->edit($user->getWikiTitle(),
				"{{{$user->getWikiTitle()}/Profile}}\n\n[[Category:Users|{$user->user->user_name}]]\n");
			Wiki::inst()->protect($user->getWikiTitle().'/Profile');
		}

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
			'position' => $user->position,
			'postalcode' => $user->postalcode,
			'phone' => $user->phone,
			'twitter' => $user->twitter,
			'linkedin' => $user->linkedin,
			'skype' => $user->skype,
			'shortbio' => $user->shortbio,
			'accept_terms' => $user->accept_terms,
		);
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
				$this->redirect('/users/index/view/'.$id);
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
				$this->redirect('/users/index/view/'.$id);
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

	}
	
	public function isValidImage($values) {
		require_once('record/objects/ImageObject.class.php');
		$value = reset($values);
		$obj = new ImageObject(null, null, array());
		return $obj->checkValue($value);
	}
	
	public function mainAction() {
		if (!$this->request->user || !$this->request->user->id) return;
		$user = $this->request->user;

		require_once('Issue.class.php');
		require_once('Project.class.php');
		$issue = new Issue();
		$project = new Project();

		$this->view->user = $user;
		$this->view->issues = $issue->select()->where('user_id = %', $user->id)->order('id')->get();
		$this->view->projects = $project->select()->where('user_id = %', $user->id)->order('name')->get();
		$this->addPoFile('users.po');
		$this->view->render('index/main.html');
	}
}