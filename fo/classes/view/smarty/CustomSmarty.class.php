<?php

require_once 'view/smarty/dist/Smarty.class.php';
require_once 'view/smarty/GettextPO.class.php';

class CustomSmarty extends Smarty {
	protected $currDir = '';
	protected $locale;
	protected $customPoFiles = array();

	public function __construct($locale) {
		if (!$locale)
			throw new Exception('Attempt to create CustomSmarty without giving a locale.');
		parent::__construct();
		$this->compile_dir = $_SERVER['DOCUMENT_ROOT'].'/../cache/templates/';
		$this->locale = reset(explode('_', $locale));
		$this->register_prefilter(array($this, 'smarty_i18n_prefilter'));
		$this->register_postfilter(array($this, 'smarty_i18n_postfilter'));
	}

	// -- Translation filters
	// Translated are strings of the form ##message##
	// Strings are printf'ed, give parameters as: ##msg:par1:par2:...##
	// Plural strings must have the number in the first parameter.
	public $parent_resource = null;
	private $poFiles = array();
	private $smartyCompiler = null;
	private $currentPOs = null;
	private $poStack = array();

	// Finds all the references to PO files in the source and returns
	// their absolute paths.
	// $sourcePath - The path where the source is located
	// $source     - The template source
	private function getPOfiles($sourcePath, $source) {
		preg_match_all('/{pofile\s+([^}]+)}/', $source, $poFiles, PREG_PATTERN_ORDER);
		$result = array();
		foreach ($poFiles[1] as $poFile) {
			$file = $this->locale.'/'.$poFile.'.po';
			if (file_exists($sourcePath.'/../locales/'.$file))
				$result[]= $sourcePath.'/../locales/'.$file;
			elseif (file_exists($sourcePath.'/../../locales/'.$file))
				$result[]= $sourcePath.'/../../locales/'.$file;
			elseif (file_exists($sourcePath.'/../../../locales/'.$file))
				$result[]= $sourcePath.'/../../../locales/'.$file;
			elseif (file_exists($_SERVER['DOCUMENT_ROOT'].'/../locales/'.$file))
				$result[]= $_SERVER['DOCUMENT_ROOT'].'/../locales/'.$file;
			else {
				throw new Exception('Could not load language file: '.$poFile.'.po in '.$sourcePath);
			}
		}
		foreach ($this->customPoFiles as $poFile) {
			$result[] = $poFile['path'] . '/' . $this->locale . '/' . $poFile['file'];
		}
		return $result;
	}

	public function addPoFile($file, $path) {
		$this->customPoFiles[] = array('file' => $file, 'path' => $path);
	}

	public function _is_compiled($resource_name, $compile_path) {
		$isCompiled = parent::_is_compiled($resource_name, $compile_path);
		if ($isCompiled && !(strpos($resource_name, ':') > 1)) {
			// Check if we have PO files that have changed
			$_params = array('resource_name' => $resource_name, 'resource_base_path' => $this->template_dir);
			$this->_parse_resource_name($_params);
			$poFiles = $this->getPOfiles(dirname($resource_name), file_get_contents($_params['resource_name']));
			$templateTime = filemtime($compile_path);
			foreach ($poFiles as $file) {
				if (filemtime($file) > $templateTime) {
					$isCompiled = false;
					break;
				}
			}
		} elseif ($isCompiled && strpos($resource_name, 'string:') !== false) {
			$poFiles = $this->getPOfiles($this->template_dir, $resource_name);
			$templateTime = filemtime($compile_path);
			foreach ($poFiles as $file) {
				if (filemtime($file) > $templateTime) {
					$isCompiled = false;
					break;
				}
			}
		}
		return $isCompiled;
	}

	public function translate($match) {
		foreach ($this->currentPOs as $po) {
			$entry = $po->getEntry($match[1]);
			if ($entry) break;
		}
		if (!$entry) {
			throw new Exception('Unknown translation id '.$match[0].' in '.$this->smartyCompiler->_current_file);
		}
		if ($entry['plural']) {
			if ($match[2]=='' || $match[2][0]!=':')
				throw new Exception('Missing argument for plural string '.$match[0]);
			$args = explode(':', substr($match[2], 1));
			$this->smartyCompiler->_parse_vars_props($args);
			$php = '{php}switch (_pluralfunc('.$args[0].')) {'."\n";
			foreach ($entry['msgstr'] as $key => $str) {
				$php.= 'case '.$key.': printf(\''.addslashes($str).'\', '.implode(',', $args).'); break;'."\n";
			}
			$php.= '}{/php}';
			return $php;
		} else {
			if ($match[2]!='') {
				$args = explode(':', substr($match[2], 1));
				$this->smartyCompiler->_parse_vars_props($args);
				return '{php} printf(\''.addslashes($entry['msgstr']).'\', '.implode(',', $args).'); {/php}';
			} else
				return $entry['msgstr'];
		}
	}

	public function smarty_i18n_prefilter($source, &$smarty) {
		// Check for included PO files
		$poFiles = $this->getPOfiles(dirname($smarty->_current_file), $source);
		$source = preg_replace('/{pofile\s+([^}]+)}/', '', $source);

		if (count($poFiles)==0)	{
			$this->currentPOs = array();
			$this->poStack[]= array();
			return $source;
		}

		$this->smartyCompiler = &$smarty;
		$POs = array();
		foreach ($poFiles as $poFileName) {
			if (!isset($this->poFiles[$poFileName])) {
				$po = new GettextPO($poFileName);
				$this->poFiles[$poFileName] = $po;
				$POs[]= $po;
			} else {
				$POs[]= $this->poFiles[$poFileName];
			}
		}
		$this->poStack[]= ($this->currentPOs = $POs);

		return @preg_replace_callback('/##(.+?)((?::.+?)*)##/', array($this, 'translate'), $source);
	}

	public function smarty_i18n_postfilter($source, &$smarty) {
		foreach ($this->currentPOs as $po) {
			if ($po->hasPlurals) {
				$source = '<?php if (!function_exists(\'_pluralfunc\')) { function _pluralfunc($n) { return (int)('.$po->getPHPplural().'); } } ?>'."\n".$source;
				break;
			}
		}
		array_pop($this->poStack);
		$this->currentPOs = end($this->poStack);
		return $source;
	}
	// -- End translation filters

	public function fetch($resource_name, $cache_id = null, $compile_id = null, $display = false) {
		// Use absolute paths so we don't get conflicts with same-name files in different dirs
		$name = $resource_name;
		if (!(strpos($name, ':') > 1)) {
			$name = realpath($name);
			if (!$name)
				$this->trigger_error('Unable to find resource: '.$resource_name);
		}

		// Generate a different compile ID for each language
		$compile_id = $this->locale.'-'.md5($name);

		// Now call parent method
		return parent::fetch($name, $cache_id, $compile_id, $display);
	}

	public function __get($name) {
		if ($name === 'locale')
			return $this->locale;
		else
			throw new NoSuchPropertyException('Undefined property: '.get_class($this).'::$'.$name);
	}

	// overrride of smarty generated file names for string sources
	function _get_auto_filename($auto_base, $auto_source = null, $auto_id = null) {
		if (!isset($auto_source) || !ereg('^string:',$auto_source))
			return parent::_get_auto_filename($auto_base, $auto_source, $auto_id);

		$_compile_dir_sep =  $this->use_sub_dirs ? DIRECTORY_SEPARATOR : '^';
		$_return = $auto_base . DIRECTORY_SEPARATOR;

		if (isset($auto_id)) {
			// make auto_id safe for directory names
			$auto_id = str_replace('%7C',$_compile_dir_sep,(urlencode($auto_id)));
			// split into separate directories
			$_return .= $auto_id . $_compile_dir_sep;
		}

		$_filename = md5($auto_base);
		$_crc32 = sprintf('%08X', crc32($auto_source));
		// prepend %% to avoid name conflicts with
		// with $params['auto_id'] names
		$_crc32 = substr($_crc32, 0, 2) . $_compile_dir_sep . substr($_crc32, 0, 3) . $_compile_dir_sep . $_crc32;
		$_return .= '%%' . $_crc32 . '%%' . $_filename;

		return $_return;
	}

}
?>
