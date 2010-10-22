<?php

/*
	Creation: config

	In: current page
	For number pager: # of elements in current set

	Out: where, limit
*/

abstract class Pager {
	protected $currPage = '';
	protected $currPath = '';
	protected $child    = null;

	public function __construct($child = null) {
		if ($child)
			$this->addChild($child);
	}

	public function addChild(Pager $pager) {
		$this->child = $pager;
	}
/*
	public function getParent()   { return $this->parent; }
	public function getchild() { return $this->child; }
*/

	public function getChild() {
		return $this->child;
	}

	protected function setCurrPage($currPage, $currPath) {
		$pos = strpos($currPage, '.');
		if ($pos !== false)	{
			$this->currPage = substr($currPage, 0, $pos);
			if ($this->child)
				$this->child->setCurrPage(substr($currPage, $pos+1), $currPath.$this->currPage.'.');
		} else
			$this->currPage = $currPage;
		$this->currPath = $currPath;
	}

	public function setPage($page) {
		$this->setCurrPage($page, '');
	}

	public function getLimit() {
		if ($this->child)
			return $this->child->getLimit();
		else
			return '';
	}

	public function getWhere($column) {
		if ($this->child)
			return $this->child->getWhere($column);
		else
			return '';
	}

	abstract public function pageList();
}

?>