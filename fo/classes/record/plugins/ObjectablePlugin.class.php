<?php

require_once 'record/plugins/RecordPlugin.abstract.php';
require_once 'record/objects/FileObject.class.php';
require_once 'record/objects/ImageObject.class.php';

class ObjectablePlugin extends RecordPlugin {
	protected $objects = array();

	public function init() {
		foreach ($this->options as $key => &$value) {
			if (isset($value['type']) && isset($value['args'])) {
				$value['type'] .= 'Object';				
				$this->objects[$key] = $value;
				$this->record->setDataListener($key, $this);
			}
		}
	}

	protected function initObject($name) {
		if (!is_object($this->objects[$name])) {
			$value = $this->record->getData($name);
			$this->objects[$name] = new $this->objects[$name]['type']($this, $value, $this->objects[$name]['args']);
			$this->objects[$name]->init();
		}
	}

	public function __get($name) {
		$this->initObject($name);
		return $this->objects[$name];
	}

	public function __set($name, $value) {
		$this->initObject($name);
		$b = $this->objects[$name]->set($value);
		if (!$b && @$this->options[$name]['args']['throw_exception'])
			throw new Exception($name.': set failed');
	}

	public function preSave(RecordEvent $event) {
		foreach ($this->objects as $name => $obj) {
			if (is_object($obj) && $obj->dirty)
				$this->record->setData($name, $obj->value);
		}
	}

	public function postSave(RecordEvent $event) {
		foreach ($this->objects as $name => $obj) {
			if (is_object($obj))
				$obj->cleanup();
		}
	}

	public function postDelete(RecordEvent $event) {
		foreach ($this->objects as $name => $value) {
			$this->initObject($name);
			$this->objects[$name]->delete();
			$this->objects[$name]->cleanup();
		}
	}

	public function dirtyRecord() {
		$this->record->dirty = true;
	}
}

?>