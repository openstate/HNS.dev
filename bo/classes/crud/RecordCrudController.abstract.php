<?php

require_once 'crud/CrudController.abstract.php';

abstract class RecordCrudController extends CrudController {
	protected $recordClass = '';
	
	protected function listData($pager) {
		require_once $this->recordClass.'.class.php';
		$obj = new $this->recordClass();
		
		$pager->setCount($obj->select()->getCount());
		
		$data = array();
		$items = $obj->select()
					->order($this->sortData['column'] . ' ' . $this->sortData['direction'])
					->limit($pager->limit, $pager->offset)
					->get();
		foreach ($items as $item) {
			$data[$item->id] = $item->toArray();
		}
		return $data;
	}
	
	protected function loadData($id) {
		require_once $this->recordClass.'.class.php';
		$obj = new $this->recordClass();
		try {
			$obj->load($id);
		} catch(RecordNotFoundException $e) {}
		return $obj->toArray();
	}
	
	protected function saveData($id, $data) {
		require_once $this->recordClass.'.class.php';
		$obj = new $this->recordClass();
		if ($id) {
			try {
				$obj->load($id);
			} catch(RecordNotFoundException $e) {}
		}
		foreach($obj->toArray() as $key => $value) {
			if (array_key_exists($key, $data)) {
				$obj->$key = $data[$key];
			}
		}
		$obj->save();
	}
	
	protected function deleteData($ids) {
		require_once $this->recordClass.'.class.php';
		foreach ($ids as $id) {
			$obj = new $this->recordClass();
			$obj->load($id);
			$obj->delete();
		}
	}
}