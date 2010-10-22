<?php

require_once 'Controller.abstract.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../formgen/formgen/core/FormInstance.class.php';

class LoggingLoggingController extends Controller {
	protected $limit = 100;

	protected function formatDate($date) {
		return $date['day'] && $date['month'] && $date['year'] ? (
			$date['hour'] && $date['minute'] ? $date['full'] : substr($date['full'], 0, 10).' 00:00:00'
		)  : false;
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
	
		$form = new FormInstance(dirname(__FILE__).'/../forms/logging/filter.form');

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
				case 'user_id': $where .= ' AND user_id = '.(int) $value; $hasFilter = true; break;
				case 'start': $value = $this->formatDate($value);
				              if ($value) { $where .= ' AND timestamp >= \''.$value.'\''; $hasFilter = true; }
				              break;
				case 'end': $value = $this->formatDate($value);
				            if ($value) { $where .= ' AND timestamp < \''.$value.'\''; $hasFilter = true; }
				            break;
				case 'hash': $where .= ' AND hash = \''.pg_escape_string($value).'\''; $hasFilter = true; break;
			}
		}
		$count = DBs::inst(DBs::LOGGING)->query(
			'SELECT count(*) FROM api_log WHERE %l', $where
		)->fetchCell();
		$data = DBs::inst(DBs::LOGGING)->query(
			'SELECT id, user_id, timestamp, hash FROM api_log WHERE %l ORDER BY timestamp LIMIT %',
			$where, $this->limit
		)->fetchAllRows();
		
		$this->view->form = $form;
		$this->view->data = $data;
		$this->view->count = $count;
		$this->view->limit = $this->limit;
		$this->view->filter = $hasFilter ? 1 : $this->request->getGET('filter', 0);
		$this->view->has_filter = $hasFilter;
		$this->view->users = $users;

		$this->view->render('logging/index.html');
	}

	public function detailAction() {
		$id = $this->request->getParam(0);
		if (!$id) throw BadRequestException();
		
		$row = DBs::inst(DBs::LOGGING)->query('SELECT * FROM api_log WHERE id = %', (int) $id)->fetchRow();
		if (!$row) throw BadRequestException();
		
		$row['user'] = DBs::inst(DBs::HNSDEV)->query('SELECT name FROM usr_users WHERE id = %', (int) $row['user_id'])->fetchCell();
		
		$this->view->row = $row;
		$this->view->render('logging/detail.html');
	}

}

?>