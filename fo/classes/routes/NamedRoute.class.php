<?php

require_once 'routes/RouteInterface.interface.php';
require_once 'Destination.class.php';

class NamedRoute implements RouteInterface {

	protected $parts = array();
	protected $defaults = array();
	protected $requirements = array();
	protected $staticCount = 0;
	protected $vars = array();
	protected $variables = array();
	protected $parameters = array();
	protected $values = array();


	public function __construct($route, $defaults = array(), $requirements = array()) {
		$route = trim($route, '/');

		$this->defaults = (array)$defaults;
		$this->requirements = (array)$requirements;

		if ($route != '') {
			foreach (explode('/', $route) as $pos => $part) {
				if (substr($part, 0, 1) == ':') {
					$name = substr($part, 1);
					$regex = (isset($this->requirements[$name]) ? $this->requirements[$name] : null);
					$this->parts[$pos] = array('name' => $name, 'regex' => $regex);
					$this->variables[] = $name;
				} else {
					$this->parts[$pos] = array('regex' => $part);
					if ($part != '*') {
						$this->staticCount++;
					}
				}
			}
		}
	}


	public function match($path) {
		$pathStaticCount = 0;
		$defaults = $this->defaults;

		if (count($defaults)) {
			$unique = array_combine(array_keys($defaults), array_fill(0, count($defaults), true));
		} else {
			$unique = array();
		}

		$path = trim($path, '/');
		if ($path != '') {
			$path = explode('/', $path);

			foreach ($path as $pos => $pathPart) {
				if (!isset($this->parts[$pos])) {
					return false;
				}

				if ($this->parts[$pos]['regex'] == '*') {
					$this->parameters = array_slice($path, $pos);
					break;
				}

				$part = $this->parts[$pos];
				$name = isset($part['name']) ? $part['name'] : null;
				$pathPart = urldecode($pathPart);

				if ($name === null) {
					if ($part['regex'] != $pathPart) {
						return false;
					}
				} elseif ($part['regex'] == null) {
					if (strlen($pathPart) == 0) {
						return false;
					}
				} else {
					$regex = '/' . $part['regex'] . '/iu';
					if (!preg_match($regex, $pathPart)) {
						return false;
					}
				}

				if ($name !== null) {
					// It's a variabe. Setting a value
					$this->values[$name] = $pathPart;
					$unique[$name] = true;
				} else {
					$pathStaticCount++;
				}
			}
		}

		$return = $this->values + $this->parameters + $this->defaults;

		// Check if all static mappings have been met
		if ($this->staticCount != $pathStaticCount) {
			return false;
		}

		$settings = $this->values + $this->defaults;
		$destination = new Destination();
		$destination->module = (isset($settings['module']) ? $settings['module'] : null);
		$destination->controller = (isset($settings['controller']) ? $settings['controller'] : null);
		$destination->action = (isset($settings['action']) ? $settings['action'] : null);
		$destination->parameters = $this->parameters;
		return $destination;
	}

	public function assemble(Destination $destination) {
		$url = array();
		$flag = false;
		$data = array();

		$data['module'] = $destination->module;
		$data['controller'] = $destination->controller;
		$data['action'] = $destination->action;

		foreach (array('module', 'controller', 'action') as $key) {
			if (!in_array($key, $this->variables) &&  $data[$key] != $this->getDefault($key) && $this->getDefault($key) !== null) {
				return false;
			}
		}

		foreach ($destination->parameters as $param)
			$data[] = $param;

		foreach ($this->parts as $key => $part) {

			 $resetPart = false;
			if (isset($part['name']) && array_key_exists($part['name'], $data) && $data[$part['name']] === null) {
				$resetPart = true;
			}



			if (isset($part['name'])) {
				if (isset($data[$part['name']])) {
					$url[$key] = $data[$part['name']];
					unset($data[$part['name']]);
				} elseif (!$resetPart && isset($this->values[$part['name']])) {
					$url[$key] = $this->values[$part['name']];
				} elseif (!$resetPart && isset($this->parameters[$part['name']])) {
					$url[$key] = $this->parameters[$part['name']];
				} elseif (isset($this->defaults[$part['name']])) {
					$url[$key] = $this->defaults[$part['name']];
				}
			} else {
				if ($part['regex'] != '*') {
					$url[$key] = $part['regex'];
				} else {
					//$data += $this->parameters;
					foreach ($data as $var => $value) {
						if ($value !== null && $value !== $this->getDefault($var) ) {
							if (!is_int($var)) {
								$url[$var] = $value;
							} else {
								$url[] = $value;
							}
							$flag = true;
						}
					}
				}
			}
		}

		$return = '';
		foreach (array_reverse($url, true) as $key => $value) {
			if ($flag || !isset($this->parts[$key]['name']) || $value !== $this->getDefault($this->parts[$key]['name'])) {
				$return = '/' . $value . $return;
				$flag = true;
			}
		}

		return '/'.trim($return, '/');
	}

	public function getDefault($name) {
		if (isset($this->defaults[$name])) {
			return $this->defaults[$name];
		}
		return null;
	}
}