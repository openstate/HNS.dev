<?php
require_once 'ViewHelper.class.php';

//base class for redirecting etc
abstract class Response {

	protected $body = array();
	protected $debug = false;
	protected $debugContent = array();
	protected $layout; // class that wraps the baseTemplate for the page
	protected $viewHelper; // class responsible for rendering blocks in the view and reverse routing

	public function setLayout(Layout $layout) {
		$this->layout = $layout;
		return $this;
	}

	public function setViewHelper(ViewHelper $viewHelper) {
		$this->viewHelper = $viewHelper;
	}

	public function getViewHelper() {
		return $this->viewHelper;
	}

	public function getLayout() {
		return $this->layout;
	}

	public function createView($type = 'smarty') { // TODO, aparte factory class??
		$type = ucfirst($type);
		require_once 'view/'.$type.'View.class.php'; // TODO exception als de class niet gevonden kan worden?
		$viewClass = $type.'View';
		$view = new $viewClass();
		if($this->viewHelper)
			$view->setViewHelper($this->viewHelper);
		return $view;
	}

	public function setDebug($debug) {
		$this->debug = (bool)$debug;
	}

	public function getDebug() {
		return $this->debug;
	}

	public function appendBody($content) {
		$this->body[] = $content;
	}

	public function outputBody() {
		foreach ($this->body as $content) {
			echo $content;
		}
	}

	public function sendResponse() {
        $this->outputBody();
        if ($this->debug) {
			foreach($this->debugContent as $debug) {
				var_dump($debug);
			}
			$db = DBs::inst(DBs::SYSTEM);
			$queries = $db->getLastQuery(-1);
			echo '<pre>';
			$time = 0;
			foreach ($queries as $query) {
				$time += $query[1];
			}
			echo '#queries: '.count($queries)."\n";
			echo 'Time: '.$time."\n";
			echo '</pre>';
			RecordCache::cacheStats();
		}
		Session::writeClose();
    }

	public function addDebug($parameter) {
		if ($this->debug) {
			$args = func_get_args();
			$this->debugContent[] = $args;
		}
	}

	// proxy functions
	public function route($interalUrl) {
		return $this->viewHelper->reverseRoute($interalUrl);
	}

}
