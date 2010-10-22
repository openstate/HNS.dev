<?php

require_once 'view/smarty/CustomSmarty.class.php';
require_once 'view/smarty/SmartyModifiers.class.php';
require_once 'view/Viewable.interface.php';
require_once 'ViewHelper.class.php';
require_once 'Translater.class.php';

class SmartyView implements Viewable {
	protected $template;
	protected $templatePath;
	protected $smarty;
	protected $viewHelper;
	protected $locale;
	protected $translater;

	public function __construct($request = null) {
		$this->locale = $request ? $request->user->getLocale() : 'en_EN';
		$this->smarty = new CustomSmarty($this->locale);
		$this->smarty->template_dir = $_SERVER['DOCUMENT_ROOT'].'/../templates/';
	}

	public function setViewHelper(ViewHelper $viewHelper) {
		$this->viewHelper = $viewHelper;
		new SmartyModifiers($this->smarty, $viewHelper, $this->locale);
	}

	public function getViewHelper() {
		return $this->viewHelper;
	}

	public function setTranslater(Translater $translater) {
		$this->translater = $translater;
		$this->smarty->register_modifier('translate', array($translater, 'translate'));
	}

	public function getTranslater() {
		return $this->translater;
	}

	public function registerModifier($name, $callback) {
		$this->smarty->register_modifier($name, $callback);
	}

	public function assignByRef($key, $value) {
		$this->smarty->assign_by_ref($key, $value);
	}

	public function __set($key, $value) {
		$this->smarty->assign($key, $value);
	}

	public function __get($key) {
		return $this->smarty->get_template_vars($key);
	}

	public function __isset($key) {
		return !is_null($this->smarty->get_template_vars($key));
	}

	public function __unset($key) {
		$this->smarty->clear_assign($key);
	}

	public function clear() {
		$this->smarty->clear_all_assign();
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

		if (empty($this->template)) {
			throw new Exception('No template set');
		}

		if (!file_exists($this->templatePath.'/'.$this->template)) {
			throw new Exception('The file' . $this->templatePath.'/'.$this->template . 'does not exist');
		}
		$result = $this->smarty->fetch($this->templatePath.'/'.$this->template);

		echo $result;
	}

	public function getEngine() {
		return $this->smarty;
	}

	public function __call($func, $args) {
		if (isset($this->smarty->_plugins['modifier'][$func]))
			return call_user_func_array($this->smarty->_plugins['modifier'][$func][0], $args);
		else {
			$file = dirname(__FILE__).'/smarty/dist/plugins/modifier.'.$func.'.php';
			if (file_exists($file)) {
				$smarty = $this->smarty;
				require_once $file;
				$func = 'smarty_modifier_'.$func;
				return call_user_func_array($func, $args);
			}
		}
	}
}

?>