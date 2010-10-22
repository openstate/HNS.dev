<?php

require_once 'RecordCrudController.abstract.php';

abstract class RecordTreeCrudController extends RecordCrudController {
	protected $listPageSize = PHP_INT_MAX;

	protected $indentStack = array();
	protected $rightMax = null;
	protected $leftMin = null;
	protected $allowMove = array('up' => true, 'down' => true);

	public function __construct() {
		parent::__construct();
		$this->extraColumns = array('sort' => array(
			'title' => '##crud.sort##',
			'class'	=> 'sort',
			'actions' => array(
				'sort_up'   => array(
					'name' => 'sort_up',
					'url' => 'moveup/{$id}',
					'class' => 'sort-up',
					'title' => '##crud.sort.up##',
					'description' => '##crud.sort.up##',
					'condition' => '{if "up"|allow}true{/if}',
				),
				'sort_down' => array(
					'name' => 'sort_down',
					'url' => 'movedown/{$id}',
					'class' => 'sort-down',
					'title' => '##crud.sort.down##',
					'description' => '##crud.sort.down##',
					'condition' => '{if "down"|allow}true{/if}',
				),
			),
		)) + $this->extraColumns;
		$this->extraColumns['options']['actions']['delete']['condition'] = '{if $row.left_id + 1 == $row.right_id}true{/if}';
	}

	protected function listData($pager) {
		require_once $this->recordClass.'.class.php';
		$obj = new $this->recordClass();

		$where = $this->getListWhere();
		$query = $obj->select();
		if ($where) {
			$query->where($where);
		}

		$pager->setCount($query->getCount());

		$data = array();
		$items = $query
					->order('left_id ASC')
					->order($this->sortData['column'] . ' ' . $this->sortData['direction'])
					->limit($pager->limit, $pager->offset)
					->get();

		$level = $obj->getLevels();

		foreach ($items as $item)
			$data[$item->id] = $item->toArray() + array('level' => @$level[$item->id]);
		return $data;
	}

	protected function loadData($id) {
		require_once $this->recordClass.'.class.php';
		$obj = new $this->recordClass();
		try {
			$obj->load($id);
		} catch(RecordNotFoundException $e) {}
		$data = $obj->toArray();
		if (!$id) {
			$data['parent_id'] = $this->request->getParam(0);
		}
		return $data;
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
			if (array_key_exists($key, $data) && $key != 'parent_id') {
				$obj->$key = $data[$key];
			}
		}
		$parent = new $this->recordClass();
		try {
			$parent->load((int) $data['parent_id']);
		} catch(RecordNotFoundException $e) { return; }
		if (!$id)
			$parent->addChild($obj);
		else {
			$obj->save();
			if ($parent->id != $obj->parent_id)
				$obj->moveNode($parent->id);
		}
	}

	protected function getListWhere() {
		return 'parent_id IS NOT NULL';
	}

	protected function getRanges() {
		require_once $this->recordClass.'.class.php';
		$obj = new $this->recordClass();
		$where = $this->getListWhere();
		$query = $obj->select()
			->addRecordColumns(false)
			->setFetchMode(RecordQuery::FETCH_ARRAY)
			->addExtraColumn('tree_id')
			->addExtraColumn('max(right_id)')
			->group('tree_id');
		if ($where) {
			$query = $query->where($where);
		}
		$this->rightMax = $query->get();
		$query = $obj->select()
			->addRecordColumns(false)
			->setFetchMode(RecordQuery::FETCH_ARRAY)
			->addExtraColumn('tree_id')
			->addExtraColumn('min(left_id)')
			->group('tree_id');
		if ($where) {
			$query = $query->where($where);
		}
		$this->leftMin = $query->get();
	}

	public function indent($value, $row) {
		$left = $row['left_id'];
		$right = $row['right_id'];
		$tree = $row['tree_id'];
		$id = $row['id'];

		if (!$this->rightMax) {
			$this->getRanges();
		}
		$rightMax = $this->rightMax[$tree];
		$leftMin = $this->leftMin[$tree];

		while($this->indentStack) {
			$top = array_pop($this->indentStack);
			if ($left > $top[0] && $right < $top[1]) {
				$this->indentStack[] = $parent = $top;
				break;
			}
		}
		$this->indentStack[] = array($left, $right, $id);

		$this->allowMove['up'] = $left - 1 != $parent[0] && $left != $leftMin;
		$this->allowMove['down'] = $right + 1 != $parent[1] && $right != $rightMax;

		$level = count($this->indentStack)-1;
		$class = $left + 1 < $right ? 'indent_clickable close' : 'leaf';
		if (($left - 1 == $parent[0] || $left == $leftMin) && $level == 0) {
			$class .= '_first';
		} else if ($right + 1 == $parent[1] || $right == $rightMax) {
			$class .= '_last';
		}
		if ($left + 1 < $right) {
			$class .= '" id="node_'.$id;
		}

		$classes = array($class);
		$parents = array();
		for ($i = $level-1; $i >= 1; $i--) {
			array_unshift($classes, $this->indentStack[$i][1] + 1 != $this->indentStack[$i-1][1] ? 'level' : 'blank');
		}
		if ($level) {
			array_unshift($classes, $this->indentStack[0][1] == $rightMax ? 'blank' : 'level');
		}

		return
			'<div class="indent parent-'.$row['parent_id'].'">'.
			'<div class="'.implode('">&nbsp;</div><div class="', $classes).'">&nbsp;</div>'.
			'</div>&nbsp;'.htmlspecialchars($value);
	}

	public function allow($value) {
		return $this->allowMove[$value];
	}

	protected function addIndentHeader($identifier, $title) {
		$this->addHeader($identifier, $title, false, false, '{$row.'.$identifier.'|indent:$row}');
	}

	protected function addHeader($identifier, $title, $action = false, $sortable = true, $value = false) {
		parent::addHeader($identifier, $title, $action, false, $value);
	}

	public function listAction() {
		$this->view->registerModifier('indent', array($this, 'indent'));
		$this->view->registerModifier('allow', array($this, 'allow'));
		parent::listAction();
	}

	public function moveupAction() {
		$id = $this->request->getParam(0);
		if ($id && ctype_digit($id)) {
			$this->moveupData((int) $id);
		}
		$this->redirect($this->returnUrl()); //go back to the list action
	}

	public function movedownAction() {
		$id = $this->request->getParam(0);
		if ($id && ctype_digit($id)) {
			$this->movedownData((int) $id);
		}
		$this->redirect($this->returnUrl()); //go back to the list action
	}

	protected function deleteData($ids) {
		require_once $this->recordClass.'.class.php';
		foreach ($ids as $id) {
			$obj = new $this->recordClass();
			$obj->load($id);
			if ($obj->left_id + 1 == $obj->right_id) {
				$obj->delete();
			}
		}
	}

	protected function moveupData($id) {
		require_once $this->recordClass.'.class.php';
		$obj = new $this->recordClass();
		$obj->load($id);
		$obj->moveLeft();
	}

	protected function movedownData($id) {
		require_once $this->recordClass.'.class.php';
		$obj = new $this->recordClass();
		$obj->load($id);
		$obj->moveRight();
	}


	protected function formatDropdownNode($node, $l, $r) {
		$left = $node->left_id;
		$right = $node->right_id;
		$tree = $node->tree_id;

		while($this->indentStack) {
			$top = array_pop($this->indentStack);
			if ($left > $top[0] && $right < $top[1]) {
				$this->indentStack[] = $parent = $top;
				break;
			}
		}
		$this->indentStack[] = array($left, $right);

		if (count($this->indentStack) > 3 || !(!$r && !$l || $right < $l || $left > $r || $left < $l && $right > $r)) return false;

		$indent = count($this->indentStack) > 2 ? str_repeat('&nbsp;', (count($this->indentStack)-2)*5) : '';
		if ($node->parent_id)
			$title = $node->title;
		else
			$title = $this->view->getTranslater()->translate('crud.noparent', 'crud');
		return $indent.htmlspecialchars($title);
	}

	protected function preCreateForm($form) {
		require_once $this->recordClass.'.class.php';
		$obj = new $this->recordClass();
		$nodes = $obj->select()->where('tree_id=%', 1)->order('left_id ASC')->get();
		$array = array();
		$this->indentStack = array();
		foreach ($nodes as $node)
			$array[$node->id] = $this->formatDropdownNode($node);
		$this->view->nodes = $array;
	}
	protected function preEditForm($form) {
		$this->preCreateForm($form);
	}
}

?>