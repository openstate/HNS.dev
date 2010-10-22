<?php

require_once(dirname(__FILE__).'/Pager.abstract.php');

class NumberPager extends Pager {
	protected $count, $maxPage;

	protected $pageLength = 15, $localRange = 3, $mulFactor = 2;

	public function __construct($opts = null, $child = null) {
		parent::__construct($child);
		if (is_array($opts))
			$this->setOptions($opts);
		$this->currPage = 1;
	}

	public function setOptions(array $opts) {
		if (isset($opts['localRange']))
			$this->localRange = $opts['localRange'];
		if (isset($opts['mulFactor']) && $opts['mulFactor'] >= 2)
			$this->mulFactor = $opts['mulFactor'];
		if (isset($opts['pageLength'])) {
			$this->pageLength = $opts['pageLength'];
		}
	}

	public function setCount($count) {
		$this->count = $count;
		$this->maxPage = max((int)ceil($this->count / $this->pageLength), 1);
		if ($this->currPage > $this->maxPage)
			$this->currPage = $this->maxPage;
	}

	protected function setCurrPage($currPage, $currPath) {
		parent::setCurrPage($currPage, $currPath);
		$this->currPage = (int)$this->currPage;
		if ($this->currPage < 1)
			$this->currPage = 1;
	}

	public function getLimit() { return 'LIMIT '.$this->limit.' OFFSET '.$this->offset; }

	public function pageList() {
		// Local granularity is 1
		$pages = range(1, $this->localRange);

		$delta = $this->localRange * $this->mulFactor;

		// Compensation	for high multiplication factors
		if ($this->mulFactor > 2)
			$pages[] = $delta / 2;

		// Exponential pages
		while ($delta < $this->maxPage) {
			$pages[] = $delta;
			$delta *= $this->mulFactor;
		}

		// Construct full pages array
		$hasFirstPage = $hasLastPage = false;
		$result = array();
		foreach (array_merge(array_reverse($pages), array(0), $pages) as $key => $page) {
			if ($key < count($pages))
				$page = -$page;
			$page += $this->currPage;
			if ($page == 1)              $hasFirstPage = true;
			if ($page == $this->maxPage) $hasLastPage  = true;
			if ($page >= 1 && $page <= $this->maxPage)
				$result[] = array('id' => $this->currPath.$page, 'iscurrent' => $page == $this->currPage, 'title' => $page);
		}
		if (!$hasFirstPage)
			array_unshift($result, array('id' => $this->currPath.'1', 'iscurrent' => false, 'title' => 1));
		if (!$hasLastPage)
			$result[] = array('id' => $this->currPath.$this->maxPage, 'iscurrent' => false, 'title' => $this->maxPage + 1);

		return $result;
	}

	public function __get($name) {
		if ($name == 'firstPage')
			return 1;
		else if ($name == 'prevPage') {
			if ($this->currPage > 1)
				return $this->currPage - 1;
			else
				return 1;
		} else if ($name == 'nextPage') {
			if ($this->currPage < $this->maxPage)
				return $this->currPage + 1;
			else
				return $this->maxPage;
		} else if ($name == 'lastPage')
			return $this->maxPage;
		else if ($name == 'limit')
			return $this->pageLength;
		else if ($name == 'offset')
			return ($this->currPage - 1) * $this->pageLength;
		else if ($name == 'currPage')
			return $this->currPage;
		else if ($name == 'count')
			return $this->count;
		else
			throw new Exception('Unknown property: '.get_class($this).'::$'.$name);
	}
}

?>