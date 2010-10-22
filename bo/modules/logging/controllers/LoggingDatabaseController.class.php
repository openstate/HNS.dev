<?php

require_once 'Controller.abstract.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../formgen/formgen/core/FormInstance.class.php';

class LoggingDatabaseController extends Controller {
	protected function formatDate($date) {
		return $date['day'] && $date['month'] && $date['year'] ? $date['full'] : false;
	}

	public function indexAction() {
		$form = new FormInstance(dirname(__FILE__).'/../forms/database/filter.form');

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
		
		$where = 'user_id IS NOT NULL';
		$hasFilter = false;
		foreach (array_filter($values) as $key => $value) {
			switch($key) {
				case 'start': $value = $this->formatDate($value);
				              if ($value) { $where .= ' AND timestamp >= \''.$value.'\''; $hasFilter = true; }
				              break;
				case 'end': $value = $this->formatDate($value);
				            if ($value) { $where .= ' AND timestamp <= \''.$value.'\''; $hasFilter = true; }
				            break;
			}
		}
		$raw = DBs::inst(DBs::LOGGING)->query(
			'SELECT user_id, sum(queries) AS queries, sum(time) AS time FROM api_log WHERE %l GROUP BY user_id',
			$where
		)->fetchAllRows('user_id');
		$users = DBs::inst(DBs::HNSDEV)->query('SELECT name, id FROM usr_users ORDER BY name')->fetchAllCells('id');
		$data = array();
		
		foreach ($users as $id => $user) {
			if (array_key_exists($id, $raw) && $raw[$id]['queries'])
				$data[] = array('user' => $user) + $raw[$id];
		}
		
		$this->view->form = $form;
		$this->view->data = $data;
		$this->view->filter = $hasFilter ? 1 : $this->request->getGET('filter', 0);
		$this->view->has_filter = $hasFilter;

		$this->view->render('database/index.html');
	}
}

?>