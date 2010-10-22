<?php

abstract class RecordObject {
	protected $plugin;
	protected $dirty = false;
	protected $originalValue;
	protected $value;

	protected $config = array();

	public function __construct($plugin, $value, array $config) {
		$this->plugin = $plugin;
		$this->originalValue = $this->value = $value;
		$this->config = array_merge($this->config, $config);
	}

	public function __get($name) {
		if ($name === 'dirty')
			return $this->dirty;
		elseif ($name === 'value')
			return $this->value;
		else
			throw new Exception('Undefined property: '.get_class($this).'::$'.$name);
	}

	public function __set($name, $value) {
		throw new Exception('Undefined property: '.get_class($this).'::$'.$name);
	}

	public abstract function cleanup();

	public abstract function delete();

	public abstract function set($value);

	public abstract function toArray();

	public function __toString() {
		return $this->value === null ? '' : $this->value;
	}
}
?>