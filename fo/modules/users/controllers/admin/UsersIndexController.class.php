<?php
require_once 'crud/RecordCrudController.abstract.php';
require_once 'User.class.php';

class UsersIndexController extends RecordCrudController {
	protected $recordClass = 'User';
		
	/*protected $toolbar = array(
		'add'    => array('url' => 'create', 'class' => 'add', 'title' => 'Toevoegen'),
		'delete' => array('url' => 'delete', 'class' => 'delete', 'title' => 'Verwijderen', 'check' => true),
			);*/
			
	protected $pofile = 'users.po';

	protected $form = array (
		'create' => 'admin/create.form',
		'edit'   => 'admin/edit.form'
	);
	
	protected function preDispatch() {
		$action = $this->request->getDestination()->action;
		
		switch ($action) {
			case 'index':
				$this->view->title = '##users.listtitle##';
				break;
			case 'create':	
				$this->view->title = '##users.createtitle##';
				break;
			case 'edit':
				$this->view->title = '##users.edittitle##';
				break;				
		}
	}
	
	protected function preList() {
		$this->addHeader('email', '##users.email##');
		$this->addHeader('created', '##users.created##', false, true, '{$row.created|date_format:"dd-MM-Y"} {$row.created|date_format:"HH:mm"}');

		$this->sortData['column'] = 'email';
	}

	
	protected function listData($pager) {
		require_once $this->recordClass.'.class.php';
		$obj = new $this->recordClass();
		
		$pager->setCount($obj->select()->where('t1.deleted IS NULL')->getCount());
		
		$data = array();		
		$items = $obj->select()->where('t1.deleted IS NULL')
					->order($this->sortData['column'] . ' ' . $this->sortData['direction'])					
					->limit($pager->limit, $pager->offset)
					->get();
		foreach ($items as $item) {
			$data[$item->id] = $item->toArray();
		}		
		return $data;
	}

	protected function preCreateForm($form) {
		$form->addCallback('isUniqueEmail', array($this, 'isUniqueEmail'));
		$form->addCallback('isValidEmail', array($this, 'isValidEmail'));
	}

	protected function preEditForm($form) {
		$form->addCallback('isUniqueEmail', array($this, 'isUniqueEmail'));
		$form->addCallback('isValidEmail', array($this, 'isValidEmail'));
	}
	
	public function isUniqueEmail($data) {
		$id = $this->request->getParam(0, false);
		require_once 'User.class.php';
		$user = new User();
		$select = $user->select()->where('email = %', $data['email']);
		if ($id)
			$select = $select->where('id != %', $id);
		return $select->getCount() == 0;
	}

	public function isValidEmail($data) {
		if (function_exists('checkdnsrr')) {
			list($user, $host) = explode('@', $values['email']);
    	return (checkdnsrr($host, 'MX') || checkdnsrr($host, 'A') || checkdnsrr($host, 'CNAME'));
		} else {
			return true;
		}
	}

}
