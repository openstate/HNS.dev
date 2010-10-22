<?php

require_once('Controller.abstract.php');
require_once('../formgen/formgen/core/FormInstance.class.php');
require_once('Project.class.php');
require_once('ProjectFile.class.php');

class ProjectsIndexController extends Controller {
	protected function getTitle($request = null) {
		try {
			$project = new Project();
			$title = parent::getTitle($request);
			$request = $request ? $request : $this->request;
			$project->load((int) $request->getParam(0));
			return sprintf($title, htmlspecialchars($project->name));
		} catch (Exception $e) {
			return parent::getTitle($request);
		}
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
		
		$project->user_id = $this->request->user->user_id;
		$project->save();
		
		require_once('Wiki.class.php');
		if ($project->published)
			Wiki::inst()->edit($project->getWikiTitle(), $project->getWikiContent());

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
		
		if ($this->request->getPost('clear_key', false))
			$project->key = null;
		else {
			$key = '';
			if ($values['key'])
				$key = $values['key'];
			elseif ($values['key_file']['error'] != UPLOAD_ERR_NO_FILE)
				$key = file_get_contents($values['key']['tmp_name']);
				
			if ($key)
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

		$published = $project->published;

		$project->published = (int) $values['publish'];
		$project->save();

		require_once('Wiki.class.php');
		if ($project->published)
			Wiki::inst()->edit($project->getWikiTitle(), $project->getWikiContent());
		elseif ($published)
			Wiki::inst()->delete($project->getWikiTitle());

		return $project->published;
	}

	public function createAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
		$form = new FormInstance(dirname(__FILE__).'/../forms/create.form');
		$form->addCallback('isValidImage', array($this, 'isValidImage'));
		$form->addCallback('isValidTitle', array($this, 'isValidTitle'));
		
		$this->checkProject = new Project();
	
		if ($this->request->isPost()) {
			$form->setPostData($this->request->getPost(), $this->request->getFiles());
			if ($form->isValid()) {
				$id = $this->saveProject(false, $form->getValues());
				$this->redirect('/projects/index/credentials/'.$id);
			}
		}

		$this->view->form = $form;
		$this->addPoFile('projects.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->view->render('project.html');
	}
	
	public function editAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
		$form = new FormInstance(dirname(__FILE__).'/../forms/edit.form');
		$form->addCallback('isValidImage', array($this, 'isValidImage'));
		$form->addCallback('isValidTitle', array($this, 'isValidTitle'));
		
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}
		
		if ($this->request->user->user_id != $project->user_id) {
			$this->displayLogin();
			return;
		}
		
		$this->checkProject = $project;
	
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
		$this->view->render('project.html');
	}

	public function credentialsAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
		$form = new FormInstance(dirname(__FILE__).'/../forms/credentials.form');
		$form->addCallback('isValidKey', array($this, 'isValidKey'));
		$form->addCallback('isValidKeyFile', array($this, 'isValidKeyFile'));

		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
			$newProject = $project->published === null;
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}
		
		if ($this->request->user->user_id != $project->user_id) {
			$this->displayLogin();
			return;
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
		$this->view->current_key = $project->key;
		$this->addPoFile('projects.po');
		$this->addPoFile('form.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->addPoFile('crud.po', $_SERVER['DOCUMENT_ROOT'].'/../locales');
		$this->view->render('project.html');
	}
	
	public function filesAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
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

		if ($this->request->user->user_id != $project->user_id) {
			$this->displayLogin();
			return;
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
		$this->view->render('project.html');
	}

	public function publishAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
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

		if ($this->request->user->user_id != $project->user_id) {
			$this->displayLogin();
			return;
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
		$this->view->render('project.html');
	}
	
	public function changeAction() {
		if (!$this->request->user->loggedIn) {
			$this->displayLogin();
			return;
		}
	
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}

		if ($this->request->user->user_id != $project->user_id) {
			$this->displayLogin();
			return;
		}
	
		$this->view->id = $id;
		$this->view->project = $project;
		$this->addPoFile('projects.po');
		$this->view->render('change.html');
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

		if ($project->published) {
			require_once('Wiki.class.php');
			$this->response->redirect(Wiki::inst()->redirect($project->getWikiTitle()));
			return;
		}

		if ($this->request->user->user_id != $project->user_id) {
			$this->displayLogin();
			return;
		}
	
		$files = $this->listFiles($id);
		
		if ($this->request->isPost() && $this->request->user->user_id == $project->user_id)
			foreach ($this->request->getPost('publish', array()) as $key => $value)
				if (isset($files[$key])) {
					$files[$key]->published = (int) (boolean) $value;
					$files[$key]->save();
				}

		$this->view->id = $id;
		$this->view->project = $project;
		$this->view->files = $files;
		$this->addPoFile('projects.po');
		$this->view->render('view.html');
	}

	public function filelistAction() {
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}

		if ($this->request->user->user_id != $project->user_id && !$project->published) {
			$this->displayLogin();
			return;
		}
	
		$files = $this->listFiles($id);
		
		if ($this->request->isPost() && $this->request->user->user_id == $project->user_id) {
			foreach ($this->request->getPost('publish', array()) as $key => $value)
				if (isset($files[$key])) {
					$files[$key]->published = (int) (boolean) $value;
					$files[$key]->save();
				}
			$this->redirect('/projects/index/view/'.$project->id);
		}

		$this->view->id = $id;
		$this->view->project = $project;
		$this->view->files = $files;
		$this->view->loggedIn = $this->request->user->user_id == $project->user_id;
		$this->addPoFile('projects.po');
		$this->view->render('filelist.html');
	}

	public function generateAction() {
		$key = openssl_pkey_new();
	    openssl_pkey_export($key, $priv);
		$pub = openssl_pkey_get_details($key);
		$pub = $pub['key'];
		echo(json_encode(array($priv, $pub)));
		die;
	}

	public function fileAction() {
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$file = new ProjectFile();
		try {
			$file->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}
		
		if ($this->request->user->user_id != $file->project->user_id && !($file->project->published && $file->published)) {
			$this->displayLogin();
			return;
		}
		
		$size = filesize($file->file->getPath());
		if ($size >= 1048576) $size = (int) round($size / 1048576.0).' MB';
		elseif ($size >= 1024) $size = (int) round($size / 1024.0).' kB';
		else $size .= ' B';

		$this->response->getLayout()->title = str_replace('$1', $file->filename, $this->getTitle());

		$this->view->file = $file;
		$this->view->size = $size;
		$this->addPoFile('projects.po');
		$this->view->render('file.html');
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
		
		if ($this->request->user->user_id != $file->project->user_id && !($file->project->published && $file->published)) {
			$this->displayLogin();
			return;
		}
	
		$finfo = finfo_open(FILEINFO_MIME);
		if (!$finfo)
			$mime = 'application/octet-stream';
		else {
			$mime = finfo_file($finfo, $file->file->getPath());
			finfo_close($finfo);
		}
	
		header('Content-Type: '.$mime);
		header('Content-Disposition: attachment; filename='.$file->filename);
		readfile($file->file->getPath());
		die;
	}
	
	public function grantAction() {
		$id = $this->request->getParam(0);
		if (!ctype_digit($id)) throw new NoRouteException();
		$project = new Project();
		try {
			$project->load($id);
		} catch (RecordNotFoundException $e) {
			throw new NoRouteException();
		}
		
		if ($project->granted || !$project->published)
			throw new NoRouteException();
		
		if (!$this->request->user->loggedIn || !$this->request->user->inGroup('sysop')) {
			$this->displayForbidden();
			return;
		}

		if ($this->request->isPost()) {
			require_once('HnsDevUser.class.php');
			$u = new HnsDevUser();
			$u->name = $project->name;
			$u->key = $project->key;
			$u->contact = $project->user->user_real_name ? $project->user->user_real_name : $project->user->user_name;
			$u->email = $project->user->user_email;
			$u->max_rate = 150;
			$u->max_load = 2;
			$u->save();
			
			DBs::inst(DBs::HNSDEV)->query('INSERT INTO usr_rights (user_id, "table", property, access) VALUES (%, %, %, %)',
				$u->id, '*', '*', $this->request->getPost('write', false) ? '1111' : '0001');

			$project->granted = 1;
			$project->save();

			require_once('Wiki.class.php');
			Wiki::inst()->edit($project->getWikiTitle(), $project->getWikiContent());

			$this->redirect('/projects/index/view/'.$project->id);
		}
		
		$this->view->project = $project;
		$this->addPoFile('projects.po');
		$this->view->render('grant.html');
	}
	
	public function recentAction() {
		$project = new Project();
		$this->view->projects = $project->select()->order('created DESC')->limit(5)->get();
		$this->addPoFile('projects.po');
		$this->view->render('recent.html');
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

	protected $keyCheck = null;

	public function isValidKey($values) {
		$result = (boolean) openssl_get_publickey($values['key']);
		$this->keyCheck = $result;
		return $result;
	}
		
	public function isValidKeyFile($values) {
		if ($this->keyCheck !== null) return true;
		require_once('record/objects/FileObject.class.php');
		$obj = new FileObject(null, null, array('reverseTypes' => true, 'path' => ''));
		$obj->init();
		return $obj->checkValue($values['key_file']) && (boolean) openssl_get_publickey(file_get_contents($values['key_file']['tmp_name']));
	}
	
	protected $checkProject = null;
	
	public function isValidTitle($values) {
		return $this->checkProject->isValidTitle(reset($values));
	}

}

?>