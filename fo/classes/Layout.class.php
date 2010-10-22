<?php

require_once 'ViewHelper.class.php';
require_once 'view/Viewable.interface.php';

class Layout implements Viewable {
	protected $view;
	protected $outerView;
	protected $content;
	protected static $head = array();
	protected static $javascript = array();
	protected static $style = array();
	protected $viewHelper = null;
	protected $useOuter = false;
	protected $noRender = false;
	
	public function __construct(Viewable $view) {
		$this->view = $view;
		$this->viewHelper = $view->getViewHelper();	
		$this->view->getEngine()->register_outputfilter(array($this, 'outputfilter'));
		
		$this->outerView = clone $view;
	}
	
	public function outputfilter($tpl_output, &$smarty) {
		if (strpos($tpl_output, '</head>') !== false) {
			$tpl_output = preg_replace('!\s*</head>!', '</head>', $tpl_output);
			return str_replace('</head>', "\n\t".str_replace("\n", "\n\t", $this->getHeadTags())."\n".'</head>', $tpl_output);
		} else
			return $tpl_output;
	}
	
	public function __clone() {
		$this->view = clone $this->view;
		$this->outerView = clone $this->outerView;
	}

	public function setViewHelper(ViewHelper $viewHelper) {
		$this->viewHelper = $viewHelper;
		$this->view->setViewHelper($viewHelper);
		$this->outerView->setViewHelper($viewHelper);
	}

	public function setTranslater(Translater $translater) {
		$this->view->setTranslater($translater);
	}
		
	public function getView() {
		return $this->view;
	}
	
	public function getOuterView() {
		return $this->outerView;
	}
	
	public function getViewHelper() {
		return $this->viewHelper;
	}

	public function __set($key, $value) {
		$this->view->__set($key, $value);
	}
	
	public function __isset($key) {
		return $this->view->__isset($key);
	}

	public function __unset($key) {
		$this->view->__unset($key);
	}

	public function clear() {
		$this->view->clear();
	}

	public function render($template = '', $outerTemplate = '') {	 		
		require_once('smarty_outputfilter_wiki_links.include.php');
		if ($this->useOuter) {
			ob_start();
			if ($this->noRender) {
				$content = $this->content;
			} else {
				$this->view->render($template);
				$content = ob_get_clean();			
			}
			$this->outerView->content = $content;
			$this->view->getEngine()->register_outputfilter('smarty_outputfilter_wiki_links');
			$this->outerView->render($outerTemplate);
		} else {
			$this->view->getEngine()->register_outputfilter('smarty_outputfilter_wiki_links');
			$this->view->render($template);
		}
	}
	
	public function setTemplate($template) {
		$this->view->setTemplate($template);
	}

	public function getTemplate() {
		return $this->view->getTemplate();
	}	

	public function setTemplatePath($templatePath) {
		$this->view->setTemplatePath($templatePath);
		$this->outerView->setTemplatePath($templatePath);
	}
	
	public function setOuterTemplate($template) {
		$this->useOuter = true;
		$this->outerView->setTemplate($template);
	}
	
	public function getOuterTemplate() {
		return $this->view->getTemplate();
	}	
	
	public function setOuterTempatePath($templatePath) {
		$this->view->outerView->setTemplatePath($templatePath);
	}
	
	public function setContent($content) {
		$this->content = $content;
		$this->view->content = $content;
	}
	
	// add any lines that need to be put in the head
	// examples <link> <script src="/js.file"> <meta>
	public static function addHead($head) {
		self::$head[] = $head;
	}
	
	public static function addStyle($style) {
		self::$style[] = $style;
	}
	
	public static function addJavascript($javascript) {
		self::$javascript[] = $javascript;
	}

	protected function getHeadTags() {
		return implode("\n", array_unique(array_merge(self::$head, self::$style, self::$javascript)));
	}
	
	public function setNoRender($value) {
		$this->noRender = (bool)$value;
	}
	
	public function noRender() {
		return $this->noRender;
	}

}

?>