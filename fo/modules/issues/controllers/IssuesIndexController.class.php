<?php

require_once('Controller.abstract.php');
require_once('../formgen/formgen/core/FormInstance.class.php');
require_once('Issue.class.php');

class IssuesIndexController extends Controller {
	public function saveIssue($data) {
		$issue = new Issue();
		$issue->to_new = date('c');
		$issue->title = ucfirst($data['title']);
		$issue->description = $data['description'];
		$issue->category = $data['category'];
		$issue->priority = $data['priority'];
		$issue->status = 'new';
		$issue->url = $data['url'];
		$issue->user_id = $this->request->user->user_id;
		$issue->save();
		
		require_once('Wiki.class.php');
		Wiki::inst()->create($issue->getWikiTitle(), $issue->getWikiContent());
		Wiki::inst()->protect($issue->getWikiTitle());
		
		return $issue;
	}

	public function createAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
		$form = new FormInstance(dirname(__FILE__).'/../forms/create.form');
		$form->addCallback('isValidImage', array($this, 'isValidImage'));
		
		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$issue = $this->saveIssue($form->getValues());
				$this->response->redirect('/wiki/'.$issue->getWikiTitle());
			}
		} else {
			$form->setRawdata(array('priority' => 3));
		}

		$this->view->form = $form;
		$this->addPoFile('issues.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->view->render('issue.html');
	}

	public function progressAction() {
		$this->changeStatus('progress');
	}

	public function closeAction() {
		$this->changeStatus('closed');
	}
	
	protected function changeStatus($status) {
		if (!$this->request->user->loggedIn || !$this->request->user->inGroup('sysop')) {
			$this->displayForbidden();
			return;
		}

		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$issue = new Issue();
		try {
			$issue->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}
		
		$issue->status = $status;
		$to_status = 'to_'.$status;
		$issue->$to_status = date('c');
		$issue->save();

		require_once('Wiki.class.php');
		Wiki::inst()->edit($issue->getWikiTitle(), $issue->getWikiContent());
		
		$this->response->redirect('/wiki/'.$issue->getWikiTitle());
	}
}


?>