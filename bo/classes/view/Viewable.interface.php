<?php

interface Viewable {
	public function __set($key, $value);
	public function __isset($key);
	public function __unset($key);
	public function clear();
	public function render();
	public function setTemplate($template); // TODO, handig of render verplichten mee te geven?
	public function getTemplate();
	public function setTemplatePath($templatePath);
	public function setViewHelper(ViewHelper $viewHelper);
	public function getViewHelper();
	public function setTranslater(Translater $translater);
}

?>