<?php

class RecordEvent {
	protected $skip = false;

	public function __construct() {
	}

	public function skip() {
		$this->skip = true;
		return $this;
	}

	public function __get($name) {
		if ($name == 'skip')
			return $this->skip;
	}
}