<?php

require_once 'view/Viewable.interface.php';

class View implements Viewable {
	protected $template;
	protected $templatePath;
	protected $vars = array();

	public function __construct() {
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

	public function render() {
		ob_start();	// TODO: waarom staat dit hier, dispatcher vangt alles toch al?
		include $this->template;
		print ob_get_clean();
	}

	public function __get($key) {
		if (array_key_exists($key, $this->vars))
			return $this->vars[$key];
		//throw new Exception('Variable ('.$key.') not assigned to this view');
	}
}

?>