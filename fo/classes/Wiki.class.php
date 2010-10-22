<?php

class WikiException extends Exception { }

class Wiki {
	protected static $inst = null;

	public function inst() {
		if (!self::$inst) self::$inst = new Wiki();
		return self::$inst;
	}

	protected $url = '/w/api.php';
	protected $cookieJar = '/../files/.cookiejar';
	
	protected function __construct() {
		$this->url = 'http'.(@$_SERVER['HTTPS'] ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$this->url;
		$this->cookieJar = $_SERVER['DOCUMENT_ROOT'].$this->cookieJar;
	}
	
	protected function request($method, $data, $allowError = false) {
		$data['format'] = 'json';
		$ch = curl_init($this->url.($method == 'GET' ? '?'.http_build_query($data) : ''));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		}
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
		$out = curl_exec($ch);
		if (curl_error($ch))
			$error = 'cURL error #'.curl_errno($ch).': '.curl_error($ch);
		curl_close($ch);

		if (@$error)
			throw new WikiException($error);
		$out = json_decode($out, true);
		var_dump($out);
		if (@$out['error'] && $allowError != $out['error']['code'])
			throw new WikiException('['.$out['error']['code'].'] '.$out['error']['info']);
		return $out;
	}
	
	protected function submitForm($url, $method, $data) {
		$ch = curl_init($url.($method == 'GET' ? '?'.http_build_query($data) : ''));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
		$out = curl_exec($ch);
		if (curl_error($ch))
			$error = 'cURL error #'.curl_errno($ch).': '.curl_error($ch);
		curl_close($ch);
		
		return;
	}

	protected function login() {
		$test = $this->request('GET', array('action' => 'query', 'list' => 'watchlist'), 'wlnotloggedin');
		if (@$test['error']['code'] == 'wlnotloggedin')
			$this->request('POST', array(
				'action' => 'login',
				'lgname' => 'Bot',
				'lgpassword' => 'A?L0d7G\gD#YHN6'
			));
	}

	public function edit($title, $content, array $options = array()) {
		$this->login();
		
		$query = $this->request('GET', array(
			'action' => 'query',
			'prop' => 'info|revisions',
			'intoken' => 'edit',
			'titles' => $title
		));
		
		$query = reset($query['query']['pages']);
		$data = array('token' => $query['edittoken'], 'starttimestamp' => $query['starttimestamp']);
		if (@$query['revisions']) {
			$rev = reset($query['revisions']);
			$data['basetimestamp'] = $rev['timestamp'];
		}
		
		$this->request('POST', array(
			'action' => 'edit',
			'title' => $title,
			'text' => $content,
			'md5' => md5($content),
			'bot' => 1,
		) + $options + $data);
	}

	public function create($title, $content, array $options = array()) {
		$this->edit($title, $content, $options + array('createonly' => 1));
	}
	
	public function forceEdit($title, $content, array $options = array()) {
		$this->edit($title, $content, $options + array('nocreate' => 1));
	}

	public function delete($title) {
		$this->login();
		
		$query = $this->request('GET', array(
			'action' => 'query',
			'prop' => 'info',
			'intoken' => 'delete',
			'titles' => $title
		));

		$query = reset($query['query']['pages']);

		$this->request('POST', array(
			'action' => 'delete',
			'title' => $title,
			'token' => $query['deletetoken'],
		));
	}

	protected function doProtect($title, array $protections, $expiry, array $options = array()) {
		$this->login();
		
		$query = $this->request('GET', array(
			'action' => 'query',
			'prop' => 'info',
			'intoken' => 'protect',
			'titles' => $title
		));

		$query = reset($query['query']['pages']);

		$this->request('POST', array(
			'action' => 'protect',
			'title' => $title,
			'protections' => implode('|', array_map(create_function('$k,$v', 'return $k."=".$v;'), array_keys($protections), $protections)),
			'expiry' => $expiry,
			'token' => $query['protecttoken'],
		) + $options);
	}

	public function protect($title, $expiry = 'never', array $options = array()) {
		$this->doProtect($title, array('edit' => 'sysop'), $expiry, $options);
	}

	public function unprotect($title, $expiry = 'never', array $options = array()) {
		$this->doProtect($title, array('edit' => 'all'), $expiry, $options);
	}

	public function upload($title, $file, $description = '') {
		$this->login();

		$url = str_replace('/w/api.php', '/wiki/Special:Upload', $this->url);
		$this->submitForm($url, 'POST', array(
			'wpUploadFile' => '@'.$file,
			'wpSourceType' => 'file',
			'wpDestFile' => $title,
			'wpUploadDescription' => $description,
		) + $data);
	}
	
	
}

#$_SERVER = array('DOCUMENT_ROOT' => dirname(__FILE__), 'HTTP_HOST' => 'wiki.hnsdev.gl');
#Wiki::inst()->edit('Foobar', 'baz');

?>