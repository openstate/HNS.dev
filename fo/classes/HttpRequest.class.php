<?php
require_once 'Request.abstract.php';
require_once 'Destination.class.php';

class HttpRequest extends Request {
	protected $requestUri;
	protected $pathInfo;
	protected $baseUrl;
	protected $documentRoot;
		
	protected $getVariables = array();
	protected $postVariables = array();	
	protected $filesVariables = array();

	public function __construct($site, $requestUri = null) {
		$this->setRequestUri($requestUri);
		
		if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
			array_walk_recursive($_REQUEST, array($this, 'undoQuotes'));
			array_walk_recursive($_GET,     array($this, 'undoQuotes'));
			array_walk_recursive($_POST,    array($this, 'undoQuotes'));
			array_walk_recursive($_COOKIE,  array($this, 'undoQuotes'));
		}
		
		$this->getVariables = $_GET;
		$_GET = array();
		
		$this->postVariables = $_POST;
		$_POST = array();
		
		$this->filesVariables = $_FILES;
		$_FILES = array();
		
		parent::__construct($site);
	}

	protected function undoQuotes(&$value, $key) {
		$value = stripslashes($value);
	}

	public function __clone() {
		parent::__clone();
	}

	public function getRequestUri() {
		if (null === $this->requestUri) {
			$this->setRequestUri();
		}
		return $this->requestUri;
	}

	public function setRequestUri($requestUri = null) {
		if ($requestUri === null) {
			if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch
				$requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
			} elseif (isset($_SERVER['REQUEST_URI'])) {
				$requestUri = $_SERVER['REQUEST_URI'];
			} elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
				$requestUri = $_SERVER['ORIG_PATH_INFO'];
				if (!empty($_SERVER['QUERY_STRING'])) {
					$requestUri .= '?' . $_SERVER['QUERY_STRING'];
				}
			} else {
				return $this;
			}
		} elseif (!is_string($requestUri)) {
			return $this;
		} else {
			// Set GET items, if available
			$this->getVariables = array();
			if (false !== ($pos = strpos($requestUri, '?'))) {
				// Get key => value pairs and set $_GET
				$query = substr($requestUri, $pos + 1);
				parse_str($query, $vars);
				$this->getVariables = $vars;
			}
		}

		$this->requestUri = urldecode($requestUri);
		return $this;
	}

	public function setBaseUrl($baseUrl = null) {
		if ((null !== $baseUrl) && !is_string($baseUrl)) {
			return $this;
		}

		if ($baseUrl === null) {
			$filename = basename($_SERVER['SCRIPT_FILENAME']);

			if (basename($_SERVER['SCRIPT_NAME']) === $filename) {
				$baseUrl = $_SERVER['SCRIPT_NAME'];
			} elseif (basename($_SERVER['PHP_SELF']) === $filename) {
				$baseUrl = $_SERVER['PHP_SELF'];
			} elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
				$baseUrl = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
			} else {
				// Backtrack up the script_filename to find the portion matching
				// php_self
				$path    = $_SERVER['PHP_SELF'];
				$segs    = explode('/', trim($_SERVER['SCRIPT_FILENAME'], '/'));
				$segs    = array_reverse($segs);
				$index   = 0;
				$last    = count($segs);
				$baseUrl = '';
				do {
					$seg     = $segs[$index];
					$baseUrl = '/' . $seg . $baseUrl;
					++$index;
				} while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
			}

			// Does the baseUrl have anything in common with the request_uri?
			$requestUri = $this->getRequestUri();

			if (0 === strpos($requestUri, $baseUrl)) {
				// full $baseUrl matches
				$this->baseUrl = $baseUrl;
				return $this;
			}

			if (0 === strpos($requestUri, dirname($baseUrl))) {
				// directory portion of $baseUrl matches
				$this->baseUrl = rtrim(dirname($baseUrl), '/');
				return $this;
			}

			if (!strpos($requestUri, basename($baseUrl))) {
				// no match whatsoever; set it blank
				$this->baseUrl = '';
				return $this;
			}

			// If using mod_rewrite or ISAPI_Rewrite strip the script filename
			// out of baseUrl. $pos !== 0 makes sure it is not matching a value
			// from PATH_INFO or QUERY_STRING
			if ((strlen($requestUri) >= strlen($baseUrl))
				&& ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0)))
			{
				$baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
			}
		}

		$this->baseUrl = rtrim($baseUrl, '/');
		return $this;
	}

	public function getBaseUrl() {
		if (null === $this->baseUrl) {
			$this->setBaseUrl();
		}

		return $this->baseUrl;
	}

	public function setDocumentRoot($documentRoot = null) {
		if ($documentRoot === null) {
			$filename = basename($_SERVER['SCRIPT_FILENAME']);

			$baseUrl = $this->getBaseUrl();
			if (empty($baseUrl)) {
				$documentRoot = '';
			} elseif (basename($baseUrl) === $filename) {
				$documentRoot = dirname($baseUrl);
			} else {
				$documentRoot = $baseUrl;
			}
		}

		$this->documentRoot = realpath(trim($documentRoot, '/')).'/';
		return $this;
	}


	public function getDocumentRoot() {
		if (null === $this->documentRoot) {
			$this->setDocumentRoot();
		}

		return $this->documentRoot;
	}

	 public function setPathInfo($pathInfo = null) {
		if ($pathInfo === null) {
			$baseUrl = $this->getBaseUrl();

			if (null === ($requestUri = $this->getRequestUri())) {
				return $this;
			}

			// Remove the query string from REQUEST_URI
			if ($pos = strpos($requestUri, '?')) {
				$requestUri = substr($requestUri, 0, $pos);
			}

			if ((null !== $baseUrl)	&& (false === ($pathInfo = substr($requestUri, strlen($baseUrl)))))	{
				// If substr() returns false then PATH_INFO is set to an empty string
				$pathInfo = '';
			} elseif (null === $baseUrl) {
				$pathInfo = $requestUri;
			}
		}

		$this->pathInfo = (string) $pathInfo;
		return $this;
	}

	public function getPathInfo() {
		if (empty($this->pathInfo)) {
			$this->setPathInfo();
		}

		return $this->pathInfo;
	}

	public function getGET($name = null, $default = null) {
		if ($name === null) return $this->getVariables;		
		if (isset($this->getVariables[$name])) {
			return $this->getVariables[$name];
		}
		return $default;
	}
	
	public function setGET($get) {
		$this->getVariables = $get;
	}

	public function getPOST($name = null, $default = null) {
		if ($name === null) return $this->postVariables;		
		if (isset($this->postVariables[$name])) {
			return $this->postVariables[$name];
		}
		return $default;
	}
	
	public function setPOST($post) {
		$this->postVariables = $post;
	}

	public function getFILES($name = null, $default = null) {
		if ($name === null) return $this->filesVariables;		
		if (isset($this->filesVariables[$name])) {
			return $this->filesVariables[$name];
		}
		return $default;
	}
	
	public function setFILES($files) {
		$this->filesVariables = $files;
	}
	
	public function getHeader($header) {
        if (empty($header))
            return false;

        // Try to get it from the $_SERVER array first
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (!empty($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (!empty($headers[$header])) {
                return $headers[$header];
            }
        }

        return false;
    }    
    
    public function isXmlHttpRequest() {
        return ($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
    }
    
    public function isFlashRequest() {
        return ($this->getHeader('USER_AGENT') == 'Shockwave Flash');
    }
}