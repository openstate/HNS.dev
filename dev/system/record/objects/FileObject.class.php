<?php

require_once 'record/objects/RecordObject.abstract.php';

class FileObject extends RecordObject {
	protected $originalValue;
	protected $path = '';
	protected $reverseTypes = false;
	protected $fileTypes = array();

	public function init() {
		$this->path = realpath($_SERVER['DOCUMENT_ROOT'].$this->config['path']);
		if (isset($this->config['fileTypes']))
			$this->fileTypes = $this->config['fileTypes'];
		if (isset($this->config['reverseTypes'])) {
			$this->reverseTypes = $this->config['reverseTypes'];
		}
	}

	protected function randomString($length) {
		$result = '';
		$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		for ($i = 0; $i < $length; $i++)
			$result .= $chars[mt_rand(0, strlen($chars) - 1)];
		return $result;
	}

	public function cleanup() {
		// No cleanup when there are no changes
		if ($this->originalValue == $this->value || !$this->originalValue)
			return;

		@unlink($this->path.'/'.$this->originalValue);
		$this->originalValue = $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
		$this->dirty = true;
		$this->plugin->dirtyRecord();
	}

	public function checkValue($value) {
		if (!is_array($value))
			return false;

		// Check upload info and verify against given types
		$extension = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));

		if ($value['error'] != UPLOAD_ERR_OK)
			return false;

		if ($this->reverseTypes == false && !isset($this->fileTypes[$extension])) {
			return false;
		} elseif ($this->reverseTypes && isset($this->fileTypes[$extension])) {
			return false;
		}

		return true;
	}

	public function set($value) {
		if (!$this->checkValue($value))
			return false;

		$extension = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));

		// Create unique filename
		do {
			$filename = $this->randomString(32).'.'.$extension;
		} while (file_exists($this->path.'/'.$filename));

		// Move uploaded file
		if (!move_uploaded_file($value['tmp_name'], $this->path.'/'.$filename))
			return false;

		// Remove previous uploaded file (which is not yet persisted)
		if ($this->originalValue != $this->value)
			@unlink($this->path.'/'.$this->value);

		$this->value = $filename;
		$this->dirty = true;
		$this->plugin->dirtyRecord();

		return true;
	}

	public function cloneFile() {
		$extension = reset(array_reverse(explode('.',$this->value)));
		do {
			$filename = $this->randomString(32).'.'.$extension;
		} while (file_exists($this->path.'/'.$filename) && $filename != $this->value);

		if (file_exists($this->path.'/'.$this->value)) { //can't copy non existant files...
			@copy($this->path.'/'.$this->value, $this->path.'/'.$filename);
		}
		return $filename;
	}

	public function delete() {
		$this->value = null;
		$this->dirty = true;
		$this->plugin->dirtyRecord();
	}

	public function toArray() {
		return array('name' => (string) $this->value, 'type' => '', 'size' => '', 'tmp_name' => '');
	}

	public function getLink() {
		if (!$this->value)
			return '';

		$pathInfo = pathinfo($this->value);
		return $this->config['path'].$pathInfo['filename'].'.'.$pathInfo['extension'];
	}

	public function getPath() {
		return $this->path.'/'.$this->value;
	}
}
?>