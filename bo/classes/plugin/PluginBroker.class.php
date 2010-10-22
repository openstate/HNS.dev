<?php

require_once 'plugin/Plugin.abstract.php';

class PluginBroker extends Plugin {
	protected $plugins = array();

	public function registerPlugin(Plugin $plugin, $stackIndex = null) {
		if (array_search($plugin, $this->plugins, true) !== false) {
			throw new PluginException('Plugin ' . get_class($plugin) . ' already registered');
		}

		$stackIndex = (int) $stackIndex;

		if ($stackIndex) {
			if (isset($this->plugins[$stackIndex])) {
				throw new PluginException('There is already a plugin registered at stackIndex: ' . $stackIndex);
			}
			$this->plugins[$stackIndex] = $plugin;
		} else {
			$stackIndex = count($this->plugins);
			while (isset($this->plugins[$stackIndex])) {
				++$stackIndex;
			}
			$this->plugins[$stackIndex] = $plugin;
		}

		ksort($this->plugins);
	}

	public function unregisterPlugin($plugin) {
		if ($plugin instanceof Plugin) {
			$key = array_search($plugin, $this->plugins, $true);
			if ($key === false) {
				throw new PluginException('Plugin ' . get_class($plugin) . ' never registered.');
			}
			unset($this->plugins[$key]);
		} elseif (is_string($plugin)) {
			foreach ($this->plugins as $key => $plug) {
				$type = get_class($plug);
				if ($plugin == $type) {
					unset($this->plugins[$key]);
					// no break, the same class might be registered multiple times !
				}
			}
		}
	}

	public function hasPlugin($class) {
		foreach ($this->plugins as $plugin) {
			$type = get_class($plugin);
			if ($class == $type) {
				return true;
			}
		}
		return false;
	}

	public function getPlugin($class) {
		$found = array();
		foreach ($this->plugins as $plugin) {
			$type = get_class($plugin);
			if ($class == $type) {
				$found[] = $plugin;
			}
		}

		switch (count($found)) {
			case 0:
				return false;
			case 1:
				return $found[0];
			default:
				return $found;
		}
	}

	public function getPlugins() {
		return $this->plugins;
	}

	public function routeStartup(Request $request, Response $response) {
		foreach ($this->plugins as $plugin) {
			try {
				$plugin->routeStartup($request, $response);
			} catch (Exception $e) {
				throw $e;
			}
		}
	}
	public function routeShutdown(Request $request, Response $response) {
		foreach ($this->plugins as $plugin) {
			try {
				$plugin->routeShutdown($request, $response);
			} catch (Exception $e) {
				throw $e;
			}
		}
	}
	public function dispatchLoopStartup(Request $request, Response $response) {
		foreach ($this->plugins as $plugin) {
			try {
				$plugin->dispatchLoopStartup($request, $response);
			} catch (Exception $e) {
				throw $e;
			}
		}
	}
	public function preDispatch(Request $request, Response $response) {
		foreach ($this->plugins as $plugin) {
			try {
				$plugin->preDispatch($request, $response);
			} catch (Exception $e) {
				throw $e;
			}
		}
	}
	public function postDispatch(Request $request, Response $response) {
		foreach ($this->plugins as $plugin) {
			try {
				$plugin->postDispatch($request, $response);
			} catch (Exception $e) {
				throw $e;
			}
		}
	}
	public function dispatchLoopShutdown(Request $request, Response $response) {
		foreach ($this->plugins as $plugin) {
			try {
				$plugin->dispatchLoopShutdown($request, $response);
			} catch (Exception $e) {
				throw $e;
			}
		}
	}
	public function exception(Request $request, Response $response, Exception $exception) {
		foreach ($this->plugins as $plugin) {
			try {
				$plugin->exception($request, $response, $exception);
			} catch (Exception $e) {
				throw $e;
			}
		}
	}

}