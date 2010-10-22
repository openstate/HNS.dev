<?php

require_once 'Response.abstract.php';

class HttpResponse extends Response {

	protected $headers = array();
	protected $headersRaw = array();
	protected $isRedirect = false;
	protected $httpResponseCode = 200;
	protected $headersSentThrowsException = true;

	public function setHeader($name, $value, $replace = false) {
		$this->canSendHeaders(true);
		$name  = $this->normalizeHeader($name);
		$value = (string) $value;

		if ($replace) {
			foreach ($this->headers as $key => $header) {
				if ($name == $header['name']) {
					unset($this->headers[$key]);
				}
			}
		}

		 $this->headers[] = array(
			'name'    => $name,
			'value'   => $value,
			'replace' => $replace
		);

		return $this;
	}

	public function __clone() {
		$this->destination = clone $this->destination;
	}

	protected function normalizeHeader($name) {
		$filtered = str_replace(array('-', '_'), ' ', (string) $name);
		$filtered = ucwords(strtolower($filtered));
		$filtered = str_replace(' ', '-', $filtered);
		return $filtered;
	}

	public function getHeaders() {
		return $this->_headers;
	}

	public function clearHeaders() {
		$this->_headers = array();

		return $this;
	}

	public function setRawHeader($value) {
		$this->canSendHeaders(true);
		if ('Location' == substr($value, 0, 8)) {
			$this->isRedirect = true;
		}
		$this->headersRaw[] = (string) $value;
		return $this;
	}

	public function getRawHeaders() {
		return $this->headersRaw;
	}

	public function clearRawHeaders() {
		$this->headersRaw = array();
		return $this;
	}

	public function clearAllHeaders() {
		return $this->clearHeaders()
					->clearRawHeaders();
	}

	public function sendResponse() {
		$this->sendHeaders();
		parent::sendResponse(); //send body/debug
	}

	public function setHttpResponseCode($code) {
		if (!is_int($code) || ($code < 100) || ($code > 599)) {
			return $this;
		}

		if ((300 <= $code) && (307 >= $code)) {
			$this->isRedirect = true;
		} else {
			$this->isRedirect = false;
		}

		$this->httpResponseCode = $code;
		return $this;
	}

	public function getHttpResponseCode() {
		return $this->httpResponseCode;
	}

	public function canSendHeaders($throw = false) {
		$ok = headers_sent($file, $line);
		if ($ok && $throw && $this->headersSentThrowsException) {
			require_once 'exceptions/HeadersAlreadySendException.php';
			throw new HeadersAlreadySendException('Cannot send headers; headers already sent in ' . $file . ', line ' . $line);
		}

		return !$ok;
	}
	
	public function sendHeaders() {
		// Only check if we can send headers if we have headers to send
		if (count($this->headersRaw) || count($this->headers) || (200 != $this->httpResponseCode)) {
			$this->canSendHeaders(true);
		} elseif (200 == $this->httpResponseCode) {
			// Haven't changed the response code, and we have no headers
			return $this;
		}

		$httpCodeSent = false;

		foreach ($this->headersRaw as $header) {
			if (!$httpCodeSent && $this->httpResponseCode) {
				header($header, true, $this->httpResponseCode);
				$httpCodeSent = true;
			} else {
				header($header);
			}
		}

		foreach ($this->headers as $header) {
			if (!$httpCodeSent && $this->httpResponseCode) {
				header($header['name'] . ': ' . $header['value'], $header['replace'], $this->httpResponseCode);
				$httpCodeSent = true;
			} else {
				header($header['name'] . ': ' . $header['value'], $header['replace']);
			}
		}

		if (!$httpCodeSent) {
			header('HTTP/1.1 ' . $this->httpResponseCode);
			$httpCodeSent = true;
		}

		return $this;
	}

	public function setRedirect($location, $code = 302) {
		Session::writeClose();
		if (substr($location, 0, 4) != 'http') {
			// Make an absolute path
			if ($location[0]=='/')
				$location = 'http://'.$_SERVER['HTTP_HOST'].$location;
			else {
				$location = str_replace('/./', '/', rtrim($_SERVER['PHP_SELF'], '\\/').'/'.$location);  // Current directory
				// Resolve parent directories
				do {
					$location = preg_replace('|/[^.][^/]*/\.\./|', '/', $location, -1, $count);
				} while ($count>0);
				$location = str_replace('/..', '', $location);  // Unresolved parent dirs
				$location = 'http://'.$_SERVER['HTTP_HOST'].$location;
			}
		}
		$this->canSendHeaders(true);
		$this->setHeader('Location', $location, true)
			->setHttpResponseCode($code);
		return $this;
	}

	public function redirect($location, $code = 302) {
		$this->body = array();
		$this->setRedirect($location, $code)->sendResponse();
		die;
	}
	
}