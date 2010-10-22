<?php
require_once 'crud/RecordCrudController.abstract.php';
require_once 'ApiUser.class.php';

class UsersIndexController extends RecordCrudController {
	protected $recordClass = 'ApiUser';
		
	/*protected $toolbar = array(
		'add'    => array('url' => 'create', 'class' => 'add', 'title' => 'Toevoegen'),
		'delete' => array('url' => 'delete', 'class' => 'delete', 'title' => 'Verwijderen', 'check' => true),
			);*/
			
	protected $form = 'index/apiuser.form';
	
	protected function preCreateForm() {
		$this->view->affiliates = array(0 => '') +
			DBs::inst(DBs::HNSDEV)->query('SELECT name, id FROM usr_affiliates ORDER BY name')->fetchAllCells('id');
	}
	
	protected function preEditForm() {
		$this->preCreateForm();
	}
	
	protected function saveData($id, $data) {
		foreach ($data as &$val)
			if ($val == '') $val = null;
		unset($val);
		parent::saveData($id, $data);
	}
	
	protected function preList() {
		$this->view->affiliates = array(0 => '') +
			DBs::inst(DBs::HNSDEV)->query('SELECT name, id FROM usr_affiliates ORDER BY name')->fetchAllCells('id');
		
		$this->addHeader('name', 'Naam');
		$this->addHeader('contact', 'Contactpersoon');
		$this->addHeader('email', 'E-mailadres');
		$this->addHeader('phone_number', 'Telefoonnummer');
		$this->addHeader('affiliate_id', 'Affiliate', false, false, '{assign var=aid value=$row.affiliate_id}{$affiliates.$aid}');

		$this->sortData['column'] = 'name';
		
		$this->extraColumns['options']['actions'][] = array(
			'name' => 'rights',
			'url' => 'rights/{$id}',
			'class' => 'entry-config',
			'title' => 'Rechten',
			'description' => 'Bewerk rechten voor deze gebruiker',
			'condition' => null
		);
	}

	protected $tables = array(
		'authors' => 'Auteurs',
		'citations' => 'Citaten',
		'documents' => 'Documenten',
		'functions' => 'Functies',
		'organizations' => 'Organisaties',
		'parties' => 'Partijen',
		'persons' => 'Personen',
		'persons_functions' => 'Persoon-functies',
		'petitions' => 'Petities',
		'resumes' => 'Resumes',
		'votes' => 'Stemmen',
	);
	
	protected $ignoredColumns = array('id', 'created', 'created_by', 'updated', 'updated_by', 'revision');
	
	protected function combineBits($bits) {
		$result = 0;
		foreach ($bits as $i) $result += 1 << $i;
		return sprintf('%04s', decbin($result));
	}
	
	public function rightsAction() {
		$db = DBs::inst(DBs::HNSDEV);
		$id = (int) $this->request->getParam(0);
		$user = $db->query('SELECT name FROM usr_users WHERE id = %', $id)->fetchCell();
		if (!$user)
			throw new BadRequestException();
		
		if ($this->request->isPost()) {
			$post = array_map(array($this, 'combineBits'), $this->request->getPOST());
			if (array_key_exists('all', $post)) {
				$post['*'] = $post['all'];
				unset($post['all']);
			}
			$db->query('DELETE FROM usr_rights WHERE user_id = % AND ids IS NULL', $id);
			foreach ($post as $key => $access) {
				@list($table, $column) = explode('_', $key, 2);
				if (!$column) $column = '*';
				$db->query('INSERT INTO usr_rights (user_id, "table", property, access) VALUES (%, %, %, %)',
					$id, $table, $column, $access);
			}
			$this->redirect($this->returnUrl());
		}

		$this->view->user = $user;
		$this->view->tables = $this->tables;
		$this->view->columns = $db->query('
			SELECT a.attname, c.relname
			FROM pg_attribute a
			JOIN pg_class c ON a.attrelid = c.oid
			WHERE c.relname IN (%l) AND a.attnum > 0 AND a.attisdropped = FALSE AND a.attname NOT IN (%l)',
			"'".implode("', '", array_map('pg_escape_string', array_keys($this->tables)))."'",
			"'".implode("', '", array_map('pg_escape_string', $this->ignoredColumns))."'")->fetchAllCells(false, 'relname');
		$rights = $db->query(
			'SELECT access, "table" FROM usr_rights WHERE user_id = % AND property = \'*\' AND ids IS NULL',
			$id)->fetchAllCells('table');
		$rights = $rights + $db->query(
			'SELECT access, "table"||\'_\'||property AS prop FROM usr_rights WHERE user_id = % AND property != \'*\' AND ids IS NULL',
			$id)->fetchAllCells('prop');
		if (array_key_exists('*', $rights)) {
			$rights['all'] = $rights['*'];
			unset($rights['*']);
		}
		$rights = array_map('str_split', $rights);
		$rights = array_map('array_reverse', $rights);
		$this->view->rights = $rights;
		$this->view->render('admin/rights.html');
	}
}
