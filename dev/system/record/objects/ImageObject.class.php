<?php

require_once 'record/objects/FileObject.class.php';

class ImageObject extends FileObject {
	protected $fileTypes = array(
		'gif'  => array('image/gif'),
		'jpg'  => array('image/jpeg', 'image/pjpeg'),
		'jpeg' => array('image/jpeg', 'image/pjpeg'),
		'png'  => array('image/png'),
	);

	protected $config = array(
		'default' => '_default.jpg',
	);

	public function set($value) {
		$result = parent::set($value);
		if (!$result) {
			return false;
		}

		return true;
	}

	public function checkValue($value) {
		if (!parent::checkValue($value))
			return false;

		// Check if uploaded file is a valid image		
		$data = getimagesize($value['tmp_name']);
		$mime = image_type_to_mime_type($data[2]);		
		foreach($this->fileTypes as $k => $types) {
			foreach ($types as $k2 => $type) {				
				if ($mime == $type) return true;
			}
		}

		return false;
	}

	public function cleanup() {
		// No cleanup when there are no changes
		if ($this->originalValue == $this->value || !$this->originalValue)
			return;

		$files = scandir($this->path.'/cache');
		foreach($files as $file) {
			if($file[0] == '.') continue; // skip hidden files
			$fullName = $this->path."/cache/$file";
			if(is_dir($fullName)) continue; //skip directories

			$pathInfo = pathinfo($this->originalValue);
			if(preg_match('!^'.$pathInfo['filename'].'.*$!', $file))
				@unlink($fullName);
		}
		@unlink($this->path.'/'.$this->originalValue);
		$this->originalValue = $this->value;
	}

	public function getLink() {
		if (!$this->value)
			return '';

		$pathInfo = pathinfo($this->value);
		return $this->config['path'].$pathInfo['filename'].'.'.$pathInfo['extension'];
	}	
}
?>