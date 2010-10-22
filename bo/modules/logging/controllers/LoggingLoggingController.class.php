<?php

require_once 'Controller.abstract.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../formgen/formgen/core/FormInstance.class.php';

class LoggingLoggingController extends Controller {
	protected $limit = 100;

	protected function formatDate($date) {
		return $date['day'] && $date['month'] && $date['year'] ? $date['full'] : false;
	}

	public function indexAction() {
		$users = array(0 => '') + $users = DBs::inst(DBs::HNSDEV)->query('SELECT name, id FROM usr_users ORDER BY name')->fetchAllCells('id');
	
		$form = new FormInstance(dirname(__FILE__).'/../forms/logging/filter.form');

		$values = array();
		if ($this->request->isPost()) {
			if ($this->request->getPOST('reset', 0))
				$values = array();
			else {
				$post = $this->request->getPOST();
				if (array_key_exists('start', $post) && count($post['start']) < 3)
					unset($post['start']);
				if (array_key_exists('end', $post) && count($post['end']) < 3)
					unset($post['end']);
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
				            if ($value) { $where .= ' AND timestamp <= \''.$value.'\''; $hasFilter = true; }
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

		$this->view->toolbar = array(
			'filter' => array('url' => '#', 'class' => 'search', 'title' => 'Zoeken'),
		);
		
		$this->view->render('logging/index.html');
	}

	public function detailAction() {
		$id = $this->request->getParameter(0);
		if (!$id) throw BadRequestException();
		
		$row = DBs::inst(DBs::LOGGING)->query('SELECT * FROM api_log WHERE id = %', (int) $id)->fetchRow();
		if (!$row) throw BadRequestException();
		
		$row['user'] = DBs::inst(DBs::HNSDEV)->query('SELECT name FROM usr_users WHERE id = %', (int) $row['user_id'])->fetchCell();
		
		$this->view->row = $row;
		$this->view->render('logging/detail.html');
	}

}

?>