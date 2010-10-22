<?php

class User extends Record {
	protected $tableName = 'mediawiki.mwuser';
	protected $pkColumn  = 'user_id';

	protected $config = array(
		'user_id'					=> array(),
		'user_name' 				=> array(),
		'user_real_name'			=> array(),
		'user_password'				=> array(),
		'user_newpassword'			=> array(),
		'user_newpass_time'			=> array(),
		'user_token'				=> array(),
		'user_email'				=> array(),
		'user_email_token'			=> array(),
		'user_email_token_expires'	=> array(),
		'user_email_authenticated'	=> array(),
		'user_options'				=> array(),
		'user_touched'				=> array(),
		'user_registration'			=> array(),
		'user_editcount'			=> array(),
		'user_failed_attempts'		=> array(),
		'user_forced_resets'		=> array(),
	);
	
	protected static $cookiePrefix = null;
	
	public function __construct() {
		if (!self::$cookiePrefix) {
			$dbinfo = require $_SERVER['DOCUMENT_ROOT'].'/../privates/database.private.php';
			self::$cookiePrefix = $dbinfo[DBs::SYSTEM]['database'];
		}
		parent::__construct();
	}

	public function save() {
		return;
		//throw new Exception('Operation not supported');
	}
	
	public function delete() {
		throw new Exception('Operation not supported');
	}

	/*
		Property: $loggedIn
		Stores whether the user is logged in or not.
	*/
	protected $loggedIn = false;

	/*
		Method: doLogin
		Performs the actions required to set the logged-in state on the User object.

		First the user is logged out to clear any remaining data from the previous user, then
		the user data is loaded, and the logged in flag set. If requested, the login cookie is also set.
	*/
	protected function doLogin($id) {	
		$this->logout();		
		try {
			$this->load($id);			
		} catch(Accepte_Database_Record_Exception_NotFound $e) {
			return;
		}
		$this->loggedIn = true;
	}

	/*
		Method: logout
		Logs out the user.
		The object is completely cleared on logout.
	*/
	public function logout() {		
		if (!$this->loggedIn)
			return;
		$this->data = array();
		$this->hasOneData = array();
		$this->hasManyData = array();
		$this->loggedIn = false;
	}

	protected function loadMediawikiSession() {
		if (!isset($_COOKIE[self::$cookiePrefix.'_session']))
			return array();
		$sess = @file_get_contents(ini_get('session.save_path').'/sess_'.$_COOKIE[self::$cookiePrefix.'_session']);
		if (!$sess)
			return array();
		require_once('session_real_decode.include.php');
		return session_real_decode($sess);
	}

	/**
	 * Load user data from the session or login cookie. If there are no valid
	 * credentials, initialises the user as an anonymous user.
	 * @return \bool True if the user is logged in, false otherwise.
	 */
	protected function loadFromMediawiki() {
		$session = $this->loadMediawikiSession();
		
		if (isset($_COOKIE[self::$cookiePrefix.'UserID'])) {
			$sId = intval($_COOKIE[self::$cookiePrefix.'UserID']);
			if(isset($session['wsUserID']) && $sId != $session['wsUserID'])
				// Possible collision!
				return false;
		} else if (isset( $session['wsUserID'])) {
			if ($session['wsUserID'] != 0)
				$sId = $session['wsUserID'];
			else
				return false;
		} else
			return false;

		if (isset($session['wsUserName'])) {
			$sName = $session['wsUserName'];
		} else if (isset($_COOKIE[self::$cookiePrefix.'UserName'])) {
			$sName = $_COOKIE[self::$cookiePrefix.'UserName'];
		} else
			return false;

		$passwordCorrect = false;
		$user = new User();
		try {
			$user->load($sId);
		} catch (RecordNotFoundException $e) {
			return false;
		}

		if (isset($session['wsToken']))
			$passwordCorrect = $session['wsToken'] == $user->user_token;
		else if (isset($_COOKIE[self::$cookiePrefix.'Token']))
			$passwordCorrect = $user->user_token == $_COOKIE[self::$cookiePrefix.'Token'];
		else
			# No session or persistent login cookie
			return false;

		if (($sName == $user->user_name) && $passwordCorrect)
			return $user->user_id;
		else
			# Invalid credentials
			return false;
	}

	public function inGroup($group) {
		return (boolean) $this->db->query('SELECT 1 FROM mediawiki.user_groups WHERE ug_user = % AND ug_group = %', $this->user_id, $group)->fetchCell();
	}
	
	public function getLocale() {
		if (preg_match('/^language=(.+?)$/m', $this->user_options, $match))
			$language = $match[1];
		else
			$language = 'en';
		return strtolower($language).'_'.strtoupper($language);
	}

	/*
		Method: cookieLogin
	  Logs the user in using the persistent login cookie.

		Returns:
		Whether login through this method was successful.
	*/
	public function cookieLogin() {
		$id = $this->loadFromMediawiki();

		if (!$id)
			return false;

		$this->doLogin($id);
		return $this->loggedIn;
	}

	/*
		Method: __sleep
		Specifies what properties to serialize.

		We serialize <$loggedIn>, <$prefs> and <$rights>.
	*/
	public function __sleep() {
		$ar = parent::__sleep();
		$ar[]= 'loggedIn';
		return $ar;
	}

	/*
		Method: __get
		Magic method used to enable access to properties.

		Parameters:
		$name - The name of the property to get
	*/
	public function __get($name) {
		switch ($name) {
			case 'loggedIn':
				return $this->loggedIn;
			case 'id':
				return $this->user_id;
			default:
				return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'loggedIn')
			$this->loggedIn = $value;
		else
			throw new Exception('Operation not supported');
	}
}