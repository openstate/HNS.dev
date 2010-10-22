<?php

abstract class {processorclass}Base {
	protected $data;
	{if:validate}protected $errors = array();{/if}
	protected $dataLoaded = false;
	{if:loadmany}
	protected $sortDefault = 'id';
	protected $sortDirDefault = 'asc';
	protected $sorting = array('col' => '', 'dir' => 'asc');
	protected $sortKeys;
	{/if}

	public function __construct() {
		{if:save}$this->clear();{/if}
		{if:loadmany}
		$this->sortKeys = array(
			{nulls}
		);
		{/if}
		{if:validate}
		$this->errors = array(
			{clearerrflags}
		);
		{/if}
	}

	{if:save}
	public function clear() {
		$this->data = array(
			{defaults}
		);
	}
	{/if}

	{if:validate}
	public function null() {
		$this->data = array(
			{nulls}
		);
	}

	public function processPost($post) {
		$this->setPost($post);
		if ($this->validate()) { // Success
			$this->dataLoaded = false;
			{formactions}
		}
	}

	public function setPost($post) {
		$this->null();
		// Conversions from post data to actual values
		// For example, checkboxes use $data[] = isset($post[]);
		{conversions}
		// Post data assignments
		{postassigns}
		$this->dataLoaded = true;
	}

	public function validateReduce($prev, $curr) {
		return $prev || $curr;
	}

	public function validate() {
		{validations}
		return !array_reduce($this->errors, array($this, 'validateReduce'), false);
	}
	{/if}

{if:load}
	public function loadFromObject($id) {
		require_once('{editclass}.class.php');
		$obj = new {editclass}();
		$obj->load($id);
		$this->loadData($obj);
	}

	public function loadData($obj) {
		$this->data['{id}'] = $obj->{id};
		{loadstatements|$this->data|$obj}
	}
{/if}

{if:loadmany}
	public function loadFromObject($where = '', $order = '', $limit = '') {
		require_once('{editclass}.class.php');
		$loader = new {editclass}();


		$objs = $loader->getList('', $where, $this->getOrder($order), $limit);
		$this->loadData($objs);
	}

	protected function getOrder($order = '') {
		if ($order == '') { // Use ordering based on sort columns
			if (isset($_GET['sort']) && in_array($_GET['sort'], array('asc','desc')))
				$dir = $_GET['sort'];
			else
				$dir = $this->sortDirDefault;
			if (isset($_GET['sortcol']) && array_key_exists($_GET['sortcol'], $this->sortKeys))
				$sortCol = $_GET['sortcol'];
			else
				$sortCol = $this->sortDefault;

			$order = 'ORDER BY "'.$sortCol.'" '.$dir;
			$this->sorting['col'] = $sortCol;
			$this->sorting['dir'] = $dir;
		}
		return $order;
	}

	public function loadData($objs) {
		foreach ($objs as $obj) {
			$id = $obj->{id};
			{loadstatements|$this->data[$id]|$obj}
		}
	}
{/if}

{if:save}
	public function saveToObject() {
		require_once('{editclass}.class.php');
		$obj = new {editclass}();
		if ($this->data['{id}'])
			$obj->load($this->data['{id}']);
		$this->saveProperties($obj);
		$obj->save();
		return $obj;
	}

	public function saveProperties(&$obj) {
		{savestatements|$this->data|$obj}
	}
{/if}

	public function show($smarty) {
		$smarty->assign('formdata',   $this->data);
		{if:validate}$smarty->assign('formerrors', $this->errors);{/if}
		{if:loadmany}$smarty->assign('formsort', $this->sorting);{/if}
		$smarty->display('{htmltemplate}');
	}
}

?>