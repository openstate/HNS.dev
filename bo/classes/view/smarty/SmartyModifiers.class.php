<?php

require_once 'ViewHelper.class.php';
require_once 'view/smarty/CustomSmarty.class.php';
require_once 'Accepte/Date.php';

class SmartyModifiers {
	protected $smarty;
	protected $viewHelper;
	protected $layout;
	protected $locale;

	public function __construct(CustomSmarty $smarty, ViewHelper $viewHelper, $locale) {
		$this->smarty = $smarty;
		$this->locale = $locale;
		$this->viewHelper = $viewHelper;
		$this->smarty->register_prefilter(array($this, 'prefilter'));
		$this->smarty->register_function('render', array($this, 'render'));
		$this->smarty->register_modifier('route', array($this, 'route'));
		$this->smarty->register_modifier('date_format', array($this, 'date_format'));
		$this->smarty->register_modifier('country_format', array($this, 'country_format'));

		$this->smarty->register_function('include_script', array($this, 'includeScript'));
		$this->smarty->register_function('include_style', array($this, 'includeStyle'));
		$this->smarty->register_function('meta', array($this, 'meta'));
		$this->smarty->register_block('script', array($this, 'script'));
		$this->smarty->register_block('style', array($this, 'style'));

		$this->smarty->register_function('formatget', array($this, 'formatget'));

		$this->smarty->register_resource('string',
			array(__CLASS__, 'string_get_template', 'string_get_timestamp', 'string_get_secure', 'string_get_trusted'));


		$this->smarty->register_function('thumbnail', array($this, 'thumbnail'));

		$this->smarty->register_modifier('eval', array($this, 'evalSmarty'));

		$this->smarty->register_modifier('toRGB', array($this, 'toRGB'));
	}

	public function toRGB($hex) {
		if ($hex == 'transparent') return '[0, 0, 0]';
		if ($hex[0] == '#') $hex = substr($hex, 1);
		$hex = hexdec($hex);
		$rgb = '[' . ($hex >> 16) . ',' . (($hex >> 8) & 0xFF) . ',' . ($hex & 0xFF) . ']';
		return $rgb;
	}

	/**
	 * thumbsnails an image using the parameters.
	 *
	 * @return string link to the thumbnailed image
	 * @author Harro
	 **/
	public function thumbnail($params, &$smarty) {
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

	/**
	 * returns a formatted get string where getVars is the array to be used
	 * optional skip param to skip a certain key.
	 * And key value pairs to overwrite items in the getVars
	 *
	 * @return string|boolean
	 **/
	public function formatget($params) {
		if (!isset($params['getVars'])) return false;
		$getVars = $params['getVars'];
		unset($params['getVars']);
		$skip = isset($params['skip']) ? $params['skip'] : false;
		unset($params['skip']);

		$getVars = array_merge($getVars, $params);
		if ($skip) {
			unset($getVars[$skip]);
		}
		return htmlentities('?' . http_build_query($getVars));
	}

	/*
		Formats a date according to the Zend_Date ISO format, but doesn't use Zend_Date to do it !
		@see http://framework.zend.com/manual/en/zend.date.constants.html#zend.date.constants.selfdefinedformats
	*/
	public function date_format($value, $format = 'dd MMMM YYYY') {
		if ($value === null || $value === '') {
			return '';
		}
		$date = new Accepte_Date($value);

		return $date->format($format, $this->locale);
	}

	public function country_format($value) {
		$locale = new Zend_Locale($this->locale);
		$list = $locale->getCountryTranslationList();
		return $list[$value];
	}

	protected function protectDelimiters($match) {
		$ld = $this->smarty->left_delimiter;
		$rd = $this->smarty->right_delimiter;
		return $ld.($match[0] == $ld ? 'ldelim' : 'rdelim').$rd;
	}

	protected function replaceDelimiters($match) {
		$ld = $this->smarty->left_delimiter;
		$rd = $this->smarty->right_delimiter;
		$params = explode(' ', preg_replace('/^'.preg_quote($ld, '/').'(.+?)'.preg_quote($rd, '/').'$/', '\\1', $match[1]));
		$newld = $ld.$ld;
		$newrd = $rd.$rd;
		foreach($params as &$param)
			if (strpos($param, '=') !== false) {
				list($key, $value) = explode('=', $param);
				if ($value[0] == '\'' || $value[0] == '"') {
					if (strlen($value) > 1 && $value[strlen($value)-1] == $value[0])
						$value = substr($value, 1, strlen($value)-2);
					else {
						$pos = strpos($match[2], $rd) + 1;
						$match[1] .= substr($match[2], 0, $pos);
						$match[2] = substr($match[2], $pos);
						return $this->replaceDelimiters($match);
					}
				}
				if ($key == 'ldelim') {
					$newld = $value;
					$param = false;
				} else if ($key == 'rdelim') {
					$newrd = $value;
					$param = false;
				}
			}
		unset($param);
		$newld = preg_replace_callback('/('.preg_quote($ld, '/').'|'.preg_quote($rd, '/').')/', array($this, 'protectDelimiters'), $newld);
		$newrd = preg_replace_callback('/('.preg_quote($ld, '/').'|'.preg_quote($rd, '/').')/', array($this, 'protectDelimiters'), $newrd);
		$block = preg_replace_callback('/('.preg_quote($ld, '/').'|'.preg_quote($rd, '/').')/', array($this, 'protectDelimiters'), $match[2]);
		$block = str_replace(array($newld, $newrd), array($ld, $rd), $block);
		return $ld.implode(' ', array_filter($params)).$rd.$block.$match[3];
	}

	public function prefilter($tpl_source, &$smarty) {
		$ld = preg_quote($smarty->left_delimiter, '/');
		$rd = preg_quote($smarty->right_delimiter, '/');
		return preg_replace_callback('/('.$ld.'(?:script|style).*?'.$rd.')(.+?)('.$ld.'\/(?:script|style)'.$rd.')/s', array($this, 'replaceDelimiters'), $tpl_source);
	}

	public function render($params, &$smarty) {
		if (empty($params['url'])) return '';
		$url = $params['url'];
		unset($params['url']);
		if (isset($params['_get'])) {
			return $this->viewHelper->displayBlock($url, $params['_get']);
		} else {
			return $this->viewHelper->displayBlock($url, $params);
		}
	}


	public function route($url) {
		return $this->viewHelper->reverseRoute($url);
	}

	public function includeScript($params) {
		if (!isset($params['src'])) return false;
		$type = isset($params['type']) ? $params['type'] : 'text/javascript';
		Layout::addJavascript('<script type="'.$type.'" src="'.$params['src'].'"></script>');
	}

	public function includeStyle($params) {
		if (!isset($params['src'])) return false;
		$type = isset($params['type']) ? $params['type'] : 'text/css';
		$media = isset($params['media']) ? $params['media'] : null;
		Layout::addStyle('<link rel="stylesheet" type="'.$type.'" href="'.$params['src'].'"'.($media ? ' media="'.$media.'"' : '').' />');
	}

	protected function metaParam($key, $value) {
		return str_replace('_', '-', $key).'="'.htmlspecialchars($value).'"';
	}

	public function meta($params) {
		Layout::addHead('<meta '.implode(' ', array_map(array($this, 'metaParam'), array_keys($params), $params)).' />');
	}

	public function script($params, $content, &$smarty, &$repeat) {
		if ($repeat || !$content)
			return;
		$type = isset($params['type']) ? $params['type'] : 'text/javascript';
		Layout::addJavascript('<script type="'.$type.'">'."\n".'<!--//--><![CDATA[//><!--'."\n".
			$content."\n".'//--><!]]>'."\n".'</script>');
	}

	public function style($params, $content, &$smarty, &$repeat) {
		if ($repeat || !$content)
			return;
		$type = isset($params['type']) ? $params['type'] : 'text/css';
		$media = isset($params['media']) ? $params['media'] : null;
		Layout::addStyle('<style type="'.$type.'"'.($media ? ' media="'.$media.'"' : '').'>'."\n".$content."\n".'</style>');
	}

	public function evalSmarty($source) {
		return $this->smarty->fetch('string:'.$source);
	}

	public static function string_get_template($tpl_name, &$tpl_source, &$smarty_obj) {
		$smarty_obj->parent_resource = $tpl_source;
		$tpl_source = $tpl_name;
		return true;
	}

	public static function string_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj) {
		//$tpl_timestamp = time() - 5;
		return true;
	}

	public static function string_get_secure($tpl_name, &$smarty_obj) {
		// assume no templates are secure
		return false;
	}

	public static function string_get_trusted($tpl_name, &$smarty_obj) {
		// not used for templates
	}
}

?>
