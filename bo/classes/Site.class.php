<?php

class Site {
	protected $subdomain = '';
	protected $domain = '';
	protected $tld = '';
	protected $siteName = 'frontoffice';
	protected $fullUrl;
	protected $siteData = array();
	protected $allowedLocales = array('en_EN', 'nl_NL');

	protected $sites = array();

	public function __construct() {
		$this->fullUrl = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
	}

	public function addSite($pattern, $siteName = 'frontoffice') {
		$this->sites[] = array('pattern' => $pattern, 'siteName' => $siteName);
	}

	public function process() {
		foreach ($this->sites as $site) {
			if (preg_match($site['pattern'], $this->fullUrl, $matches)) {
				$this->siteName 	= $site['siteName'];
				$this->subdomain	= isset($matches['subdomain'])	? $matches['subdomain']	: null;
				$this->domain		= isset($matches['domain'])		? $matches['domain']		: null;
				$this->tld			= isset($matches['tld'])		? $matches['tld']		: null;

				$db = DBs::inst(DBs::SYSTEM);
				$this->siteData = $db->query('SELECT * FROM sys_sites WHERE domain = %s', $this->domain)->fetchRow();
				if (!isset($_COOKIE['locale']) || !in_array($_COOKIE['locale'], $this->allowedLocales)) {
					$this->setLocale($this->siteData['locale']);
				}
				return;
			}
		}
		throw new Exception('No site specified for '.$this->fullUrl);
	}

	public function getTopDomain() {
		return $this->domain.'.'.$this->tld;
	}

	public function getFullDomain() {
		if (empty($this->subdomain)) return $this->getTopDomain();
		return $this->subdomain.'.'.$this->domain.'.'.$this->tld;
	}

	public function getDomain() {
		return $this->domain;
	}

	public function getSubdomain() {
		return $this->subdomain;
	}

	public function getTld() {
		return $this->tld;
	}

	public function getSiteName() {
		return $this->siteName;
	}

	public function getLocale() {
		return @$_COOKIE['locale'];
	}

	public function setLocale($locale) {
		if (in_array($locale, $this->allowedLocales)) {
			$_COOKIE['locale'] = $locale;
			setcookie('locale', $locale, time()+60*60*24*30, '/', '.'.$this->getTopDomain());
		}
	}

	public function __get($name) {
		if (array_key_exists($name, $this->siteData)) {
			return $this->siteData[$name];
		}
		return null;
	}



}