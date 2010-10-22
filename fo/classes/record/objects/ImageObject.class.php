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

	public function getLink($useDefault = false) {
		if (!$this->value)
			return $useDefault ? $this->config['path'].$this->config['default'] : '';

		$pathInfo = pathinfo($this->value);
		return $this->config['path'].$pathInfo['filename'].'.'.$pathInfo['extension'];
	}	

	/**
	 * thumbsnails an image using the parameters.
	 *
	 * @return string link to the thumbnailed image
	 * @author Harro
	 **/
	public function getThumbnail($width = 0, $height = 0, $type = 'fit', $position = 'center') {
		$params = array('image' => $this->getLink(), 'width' => $width, 'height' => $height, 'type' => $type, 'position' => $position);
		if (!isset($params['image']) || empty($params['image']) || !file_exists($_SERVER['DOCUMENT_ROOT'].$params['image'])) return $params['image'];
		$image = trim($params['image']);
		unset($params['image']);

		$defaults = array('width' => 0, 'height' => 0, 'type' => 'fit', 'position' => 'center');
		$params = $params + $defaults;
		if (!in_array($params['type'], array('fit', 'crop', 'stretch', 'max'))) $params['type'] = 'fit';
		ksort($params);
		if ($params['width'] == 0 && $params['height'] == 0) return $image;

		$pathinfo = pathinfo($_SERVER['DOCUMENT_ROOT'].$image);
		$directory = $pathinfo['dirname'].'/cache/';
		$filename = $pathinfo['filename'].'_'.implode('_', $params).'.png';
		if (!file_exists($directory.$filename) || filectime($_SERVER['DOCUMENT_ROOT'].$image) > filectime($directory.$filename)) {
			if (extension_loaded('imagick') && class_exists('Imagick')) {
				$im = new Imagick();
				$im->setResourceLimit( Imagick::RESOURCETYPE_MEMORY, 25 );
				try {
					$im->readImage($_SERVER['DOCUMENT_ROOT'].$image);
				} catch(ImagickException $e) {
					return $image;
				}
				$canvas = $this->resizeImage($im, $params);
				$canvas->writeImage($directory.$filename);
				$im->destroy();
				$canvas->destroy();
			} else {
				return $image;
			}
		}
		return dirname($image).'/cache/'.$filename;
	}

	/**
	 * Resizes the given image using the given parameters
	 *
	 * @return Imagick
	 * @author Harro
	 **/
	protected function resizeImage(Imagick $im, $params) {
		$width = ($params['width'] == 0 ? null : $params['width']);
		$height = ($params['height'] == 0 ? null : $params['height']);

		$geo = $im->getImageGeometry();

		switch ($params['type']) {
			case 'crop':
				if ($height && (!$width || $geo['height'] / $height < $geo['width'] / $width))
					list($w, $h) = array(0, $height);
				else
					list($w, $h) = array($width, 0);
				$im->scaleImage($w, $h);
				break;

			case 'stretch':
				if ($height && (!$width || $geo['height'] / $height > $geo['width'] / $width)) {
					list($w, $h) = array(0, $height);
				} else {
					list($w, $h) = array($width, 0);
				}
				$im->scaleImage($w, $h);
				break;

			case 'max':
				if (!$height && $width < $geo['width']) {
					$im->scaleImage($width, 0);
				} elseif(!$width && $height < $geo['height']) {
					$im->scaleImage(0, $height);
				} elseif ($height < $geo['height'] && $width < $geo['width']) {
					$im->thumbnailImage($width, $height, true);
				}
				$width = null;
				$height = null;
				break;

			case 'fit':
			default:
				if ($width == null && $geo['height'] >= $height) {
					$im->thumbnailImage($width, $height);
				} elseif ($height == null && $geo['width'] >= $width) {
					$im->thumbnailImage($width, $height);
				} elseif($geo['width'] >= $width && $geo['height'] >= $height) {
					$im->thumbnailImage($width, $height, true);
				}
				break;
		}

		$geo = $im->getImageGeometry();
		if ($width == null) $width = $geo['width'];
		if ($height == null) $height = $geo['height'];

		switch ($params['position']) {
			case 'top':
				$top = 0;
				$left = ($width-$geo['width'])/2;
				break;

			case 'bottom':
				$top = $height-$geo['height'];
				$left = ($width-$geo['width'])/2;
				break;

			case 'left':
				$top = ($height-$geo['height'])/2;
				$left = 0;
				break;

			case 'right':
				$top = ($height-$geo['height'])/2;
				$left = $width-$geo['width'];
				break;

			case 'topleft':
				$top = 0;
				$left = 0;
				break;

			case 'topright':
				$top = 0;
				$left = $width-$geo['width'];
				break;

			case 'bottomleft':
				$top = $top = $height-$geo['height'];
				$left = 0;
				break;

			case 'bottomright':
				$top = $top = $height-$geo['height'];
				$left = $width-$geo['width'];
				break;

			case 'center':
			default:
				$top = ($height-$geo['height'])/2;
				$left = ($width-$geo['width'])/2;
				break;
		}
		$canvas = new Imagick();
		$color = new ImagickPixel('transparent');
		$canvas->newImage( $width, $height, $color, 'png');
		$canvas->compositeImage( $im, imagick::COMPOSITE_OVER, $left, $top );
		return $canvas;
	}
}
?>