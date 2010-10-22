<?php

require_once 'Controller.abstract.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../formgen/formgen/core/FormInstance.class.php';
require_once 'exceptions/BadRequestException.class.php';

class LoggingVersioningController extends Controller {
	protected $limit = 100;

	protected $tables = array(
		'documents' => 'Document',
		'organisations' => 'Organisatie',
		'persons' => 'Persoon',
	);
	
	protected function formatDate($date) {
		return $date['day'] && $date['month'] && $date['year'] ? $date['full'] : false;
	}

	protected function filterDate(&$post, $field) {
		if (!array_key_exists($field, $post)) return;
		$post[$field] = array_filter($post[$field], 'strlen');
		if (!array_key_exists('Day', $post[$field]) || !array_key_exists('Month', $post[$field]) || !array_key_exists('Year', $post[$field]))
			unset($post[$field]);
		elseif (!array_key_exists('Minute', $post[$field]) && array_key_exists('Hour', $post[$field]))
			unset($post[$field]['Hour']);
		elseif (!array_key_exists('Hour', $post[$field]) && array_key_exists('Minute', $post[$field]))
			unset($post[$field]['Minute']);
	}

	public function indexAction() {
		$users = array(0 => '') + DBs::inst(DBs::HNSDEV)->query('SELECT name, id FROM usr_users ORDER BY name')->fetchAllCells('id');
	
		$form = new FormInstance(dirname(__FILE__).'/../forms/versioning/filter.form');

		$values = array();
		if ($this->request->isPost()) {
			if ($this->request->getPOST('reset', 0))
				$values = array();
			else {
				$post = $this->request->getPOST();
				$this->filterDate($post, 'start');
				$this->filterDate($post, 'end');
				$form->setPostData($post);
				if ($form->isValid())
					$values = $form->getValues();
			}
		}
		
		$where = 'TRUE';
		$hasFilter = false;
		foreach (array_filter($values) as $key => $value) {
			switch($key) {
				case 'object_table': $where .= ' AND object_table = \''.pg_escape_string($value).'\''; $hasFilter = true; break;
				case 'object_id': $where .= ' AND object_id = '.(int) $value; $hasFilter = true; break;
				case 'rev_from': $where .= ' AND revision >= '.(int) $value; $hasFilter = true; break;
				case 'rev_to': $where .= ' AND revision <= '.(int) $value; $hasFilter = true; break;
				case 'start': $value = $this->formatDate($value);
				              if ($value) { $where .= ' AND timestamp >= \''.$value.'\''; $hasFilter = true; }
				              break;
				case 'end': $value = $this->formatDate($value);
				            if ($value) { $where .= ' AND timestamp < \''.$value.'\''; $hasFilter = true; }
				            break;
				case 'user_id': $where .= ' AND user_id = '.(int) $value; $hasFilter = true; break;
			}
		}
		$count = DBs::inst(DBs::LOGGING)->query(
			'SELECT count(*) FROM revisions WHERE %l', $where
		)->fetchCell();
		$data = DBs::inst(DBs::LOGGING)->query(
			'SELECT object_table, object_id, revision, timestamp, user_id FROM revisions WHERE %l ORDER BY timestamp LIMIT %',
			$where, $this->limit
		)->fetchAllRows();
		
		$this->view->form = $form;
		$this->view->data = $data;
		$this->view->count = $count;
		$this->view->limit = $this->limit;
		$this->view->filter = $hasFilter ? 1 : $this->request->getGET('filter', 0);
		$this->view->has_filter = $hasFilter;
		$this->view->users = $users;
		$this->view->tables = array('' => '') + $this->tables;

		$this->view->render('versioning/index.html');
	}

	public function detailAction() {
		$table = $this->request->getParam(0);
		$id = $this->request->getParam(1);
		$revision = $this->request->getParam(2);
		if (!$table || !$id || !$revision) throw BadRequestException();
		
		$row = DBs::inst(DBs::LOGGING)->query(
			'SELECT * FROM revisions WHERE object_table = % AND object_id = % AND revision = %',
			$table, (int) $id, (int) $revision)->fetchRow();
		if (!$row) throw BadRequestException();
		
		$row['user'] = DBs::inst(DBs::HNSDEV)->query('SELECT name FROM usr_users WHERE id = %', (int) $row['user_id'])->fetchCell();
		$row['table'] = array_key_exists($row['object_table'], $this->tables) ? $this->tables[$row['object_table']] : $row['object_table'];
		$row['data'] = array_filter(json_decode($row['data'], true), 'is_scalar');
		
		$this->view->row = $row;
		$this->view->render('versioning/detail.html');
	}

}

?>