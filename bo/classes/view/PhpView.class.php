<?php

require_once 'view/Viewable.interface.php';
require_once 'ViewHelper.class.php';

class PhpView implements Viewable {
	protected $template;
	protected $templatePath;
	protected $vars = array();
	protected $viewHelper;

	public function __construct() {
	}
	
	public function setViewHelper(ViewHelper $viewHelper) {
		$this->viewHelper = $viewHelper;
	}

	public function getViewHelper() {
		return $this->viewHelper;
	}

	public function setTranslater() {
		// stub
	}

	public function __set($key, $value) {
		$this->vars[$key] = $value;
	}

	public function __isset($key) {
		return isset($this->vars[$key]);
	}

	public function __unset($key) {
		unset($this->vars[$key]);
	}

	public function clear() {
		$this->vars = array();
	}
	
	public function setTemplatePath($templatePath) {
		$this->templatePath = $templatePath;
	}
	
	public function setTemplate($template) {
		$this->template = $template;
	}
	
	public function getTemplate() {
		return $this->template;
	}

	public function render($template = '') {
		if($template)
			$this->setTemplate($template);
			
		ob_start();	
		include $this->templatePath .'/'. $this->template;
		print ob_get_clean();
	}

	public function __get($key) {
		if (array_key_exists($key, $this->vars))
			return $this->vars[$key];
		//throw new Exception('Variable ('.$key.') not assigned to this view');
	}
}

?>