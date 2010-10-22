<?php

require_once 'Zend/Locale.php';
require_once '../modules/users/records/User.class.php';
require_once 'session/Session.class.php';

abstract class Request {

	protected $dispatched = false;
	protected $destination;
	protected $originalDestination;
	protected $site;
	protected $zend_locale = null;
	protected $user = null;
	protected $isBlock = false;

	public function __construct($site) {
		$this->site = $site;
	}

	public function __clone() {
		if (is_object($this->destination))
			$this->destination = clone $this->destination;
	}

	public function loadUserFromSession() {
		if ($this->user == null) {
			$this->user = new User();
		}
		if (Session::isStarted()) {

			if (isset($_SESSION['user_id']) && $this->user->getPk() === false) {
				$this->user->load($_SESSION['user_id']);
				$this->user->loggedIn = true;
			}
			if (!$this->user->loggedIn) {
				$this->user->cookieLogin();
			}
		}
	}

	public function getDestination() {
		return $this->destination;
	}

	public function getOriginalDestination() {
		return $this->originalDestination;
	}

	public function setDestination(Destination $destination) {
		$this->destination = $destination;
		if (!$this->originalDestination) $this->originalDestination = $destination;
		return $this;
	}

	public function getSite() {
		return $this->site;
	}

	public function setSite($site) {
		$this->site = $site;
		return $this;
	}

	public function setDispatched($bool) {
		$this->dispatched = (bool)$bool;
		return $this;
	}

	public function isDispatched() {
		return $this->dispatched;
	}

	public function isPost() {
		if ($this->getMethod() === 'POST' && !$this->isBlock) {
			return true;
		}
		return false;
	}

	public function getMethod() {
		return $_SERVER['REQUEST_METHOD'];
	}

	public function getFullUri() {
		$result = 'http://';
		$result .= $this->getSite()->getFullDomain();
		$result .= $this->getRequestUri();
		return $result;
	}

	public abstract function getRequestUri();


	public function getNamedParams() {
		$params = $this->getDestination()->parameters;
		if (count($params) % 2 === 1)
			array_unshift($params, true);

		$namedParameters = array();
		for ($i = 0; $i < count($params); $i += 2) {
			$namedParameters[$params[$i]] = $params[$i+1];
		}
		return $namedParameters;
	}

	public function getParams() {
		return $this->getDestination()->parameters;
	}

	public function getParam($index, $default = null) {
		$params = $this->getDestination()->parameters;
		if (isset($params[$index])) {
			return $params[$index];
		}
		return $default;
	}

	public function getNamedParam($name, $default = null) {
		$namedParameters = $this->getNamedParams();
		if (isset($namedParameters[$name])) {
			return $namedParameters[$name];
		}
		return $default;
	}

	public function __get($name) {
		switch ($name) {
			case 'locale':
				if ($this->zend_locale === null) {
					$this->zend_locale = new Zend_Locale($this->user->getLocale());
				}
				return $this->zend_locale;
			break;
			case 'user':
				$this->loadUserFromSession();
				if (!is_object($this->user)) {
					$this->user = new User();
					$this->user->load(0);
				}
				return $this->user;
			break;

			case 'isBlock':
				return $this->isBlock;
			break;
		}
	}

	public function __set($name, $value) {
		switch ($name) {
			case 'isBlock':
				$this->isBlock = (bool)$value;
			break;
		}
	}
}

?>
