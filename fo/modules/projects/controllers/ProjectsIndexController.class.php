<?php

require_once('Controller.abstract.php');
require_once('../formgen/formgen/core/FormInstance.class.php');
require_once('Project.class.php');
require_once('ProjectFile.class.php');

class ProjectsIndexController extends Controller {
	protected function redirectLogin() {
		$this->response->redirect('/wiki/Special:UserLogin');
	}

	public function saveProject($id, $values) {
		$project = new Project();
		if ($id) $project->load($id);
		
		if ($this->request->getPost('clear_logo', false))
			$project->logo->delete();
		elseif ($values['logo']['error'] != UPLOAD_ERR_NO_FILE)
			$project->logo = $values['logo'];

		if ($this->request->getPost('clear_screenshot', false))
			$project->screenshot->delete();
		elseif ($values['screenshot']['error'] != UPLOAD_ERR_NO_FILE)
			$project->screenshot = $values['screenshot'];

		$project->name = $values['name'];
		$project->date = $values['date']['full'];
		$project->website = $values['website'];
		$project->description = $values['description'];
		$project->rss = $values['rss'];
		$project->license = $values['license'];
		if (!$id) {
			$project->rights_read = 1;
			$project->rights_write = 0;
		}
		
		$project->save();
		
		return $project->id;
	}
	
	public function loadProject($id) {
		$project = new Project();
		$project->load($id);
		
		return array(
			'name' => $project->name,
			'logo_img' => $project->logo->getLink(),
			'screenshot_img' => $project->screenshot->getLink(),
			'date' => $project->date,
			'website' => $project->website,
			'description' => $project->description,
			'rss' => $project->rss,
			'license' => $project->license,
		);
	}

	public function saveCredentials($id, $values) {
		$project = new Project();
		$project->load($id);
		
		$project->rights_read = (int) @$values['read'];
		$project->rights_write = (int) @$values['write'];
		
		$key = '';
		if ($values['key'])
			$key = $values['key'];
		elseif ($values['key_file']['error'] != UPLOAD_ERR_NO_FILE)
			$key = file_get_contents($values['key']['tmp_name']);
			
		if ($key) {
			//test key
			$project->key = $key;
		}
		
		$project->save();
	}

	public function loadCredentials($id) {
		$project = new Project();
		$project->load($id);
		
		return array(
			'read' => $project->rights_read,
			'write' => $project->rights_write,
			'public_key' => (boolean) $project->key,
		);
	}

	public function listFiles($id) {
		$file = new ProjectFile();
		return $file->select()->where('project_id = %', $id)->get();
	}

	public function saveFile($projectId, $id, $values) {
		$file = new ProjectFile();
		if ($id) {
			$file->load($id);
			assert($file->project_id == $projectId);
		} else {
			$file->project_id = $projectId;
		}
		
		$file->file = $values['file'];

		$file->filename = $values['file']['name'];
		$file->version = $values['version'];
		$file->description = $values['description'];
		$file->language = $values['language'];

		$file->save();
	}

	public function loadPublish($id) {
		$project = new Project();
		$project->load($id);
		
		return array(
			'publish' => $project->published,
		);
	}

	public function savePublish($id, $values) {
		$project = new Project();
		$project->load($id);

		$project->published = (int) $values['publish'];
		$project->save();

		return $project->published;
	}

	public function createAction() {
		$form = new FormInstance(dirname(__FILE__).'/../forms/create.form');
		$form->addCallback('isValidImage', array($this, 'isValidImage'));
		
		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$id = $this->saveProject(false, $form->getValues());
				$this->redirect('/projects/index/credentials/'.$id);
			}
		} else {
			//$form->setRawdata($this->loadData(false));
		}

		$this->view->form = $form;
		$this->addPoFile('projects.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('title.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->response->getLayout()->title = $this->view->translate('title.project.create', 'title');
		$this->view->render('project.html');
	}
	
	public function editAction() {
		$form = new FormInstance(dirname(__FILE__).'/../forms/edit.form');
		$form->addCallback('isValidImage', array($this, 'isValidImage'));
		
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}
		
		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$this->saveProject($id, $form->getValues());
				$this->redirect('/projects/index/change/'.$id);
			}
		} else {
			$form->setRawdata($this->loadProject($id));
		}

		$this->view->form = $form;
		$this->view->has_logo = (boolean) $project->logo->value;
		$this->view->has_screenshot = (boolean) $project->screenshot->value;
		$this->addPoFile('projects.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('title.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->response->getLayout()->title = sprintf($this->view->translate('title.project.edit', 'title'), htmlspecialchars($project->name));
		$this->view->render('project.html');
	}

	public function credentialsAction() {
		$form = new FormInstance(dirname(__FILE__).'/../forms/credentials.form');

		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
			$newProject = $project->published === null;
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}
		
		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$this->saveCredentials($id, $form->getValues());
				if ($newProject)
					$this->redirect('/projects/index/files/'.$id);
				else
					$this->redirect('/projects/index/change/'.$id);
			}
		} else {
			$form->setRawdata($this->loadCredentials($id));
		}

		$this->view->form = $form;
		$this->view->newProject = $newProject;
		$this->addPoFile('projects.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('title.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->response->getLayout()->title = sprintf($this->view->translate('title.project.credentials', 'title'), htmlspecialchars($project->name));
		$this->view->render('project.html');
	}
	
	public function filesAction() {
		$form = new FormInstance(dirname(__FILE__).'/../forms/file.form');
		$form->addCallback('isValidFile', array($this, 'isValidFile'));

		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
			$newProject = $project->published === null;
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}

		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$this->saveFile($id, false, $form->getValues());
				if ($this->request->getPost('more', false))
					$this->redirect('/projects/index/files/'.$id);
				elseif ($newProject)
					$this->redirect('/projects/index/publish/'.$id);
				else
					$this->redirect('/projects/index/change/'.$id);
			}
		} else {
			//$form->setRawdata($this->listFiles($id));
		}

		$this->view->form = $form;
		$this->view->newProject = $newProject;
		$this->addPoFile('projects.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('title.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->response->getLayout()->title = sprintf($this->view->translate('title.project.files', 'title'), htmlspecialchars($project->name));
		$this->view->render('project.html');
	}

	public function publishAction() {
		$form = new FormInstance(dirname(__FILE__).'/../forms/publish.form');

		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
			$newProject = $project->published === null;
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}

		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$publish = $this->savePublish($id, $form->getValues());
				if ($newProject)
					$this->redirect('/projects/index/view/'.$id);
				else
					$this->redirect('/projects/index/change/'.$id);
			}
		} else {
			$form->setRawdata($this->loadPublish($id));
		}

		$this->view->form = $form;
		$this->view->newProject = $newProject;
		$this->addPoFile('projects.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('title.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->response->getLayout()->title = sprintf($this->view->translate('title.project.publish', 'title'), htmlspecialchars($project->name));
		$this->view->render('project.html');
	}
	
	public function changeAction() {
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}

		$this->view->id = $id;
		$this->view->project = $project;
		$this->addPoFile('projects.po');
		$this->view->render('change.html');
		$this->addPoFile('title.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->response->getLayout()->title = sprintf($this->view->translate('title.project.change', 'title'), htmlspecialchars($project->name));
	}
	
	public function viewAction() {
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}

		$files = $this->listFiles($id);
		
		if ($this->request->isPost())
			foreach ($this->request->getPost('publish', array()) as $key => $value)
				if (isset($files[$key])) {
					$files[$key]->published = (int) (boolean) $value;
					$files[$key]->save();
				}

		$this->view->id = $id;
		$this->view->project = $project;
		$this->view->files = $files;
		$this->addPoFile('projects.po');
		$this->addPoFile('title.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->response->getLayout()->title = sprintf($this->view->translate('title.project.view', 'title'), htmlspecialchars($project->name));
		$this->view->render('view.html');
	}

	public function generateAction() {
		$key = openssl_pkey_new();
	    openssl_pkey_export($key, $priv);
		$pub = openssl_pkey_get_details($key);
		$pub = $pub['key'];
		echo(json_encode(array($priv, $pub)));
		die;
	}

	public function downloadAction() {
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$file = new ProjectFile();
		try {
			$file->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}
		
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.$file->filename);
		readfile($file->file->getPath());
		die;
	}

	public function isValidImage($values) {
		require_once('record/objects/ImageObject.class.php');
		$value = reset($values);
		$obj = new ImageObject(null, null, array());
		return $obj->checkValue($value);
	}

	public function isValidFile($values) {
		require_once('record/objects/FileObject.class.php');
		$value = reset($values);
		$obj = new FileObject(null, null, array('reverseTypes' => true, 'path' => ''));
		$obj->init();
		return $obj->checkValue($value);
	}
	
}

?>