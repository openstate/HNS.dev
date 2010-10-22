<?php
require_once('UserActivation.class.php');
require_once('UserPasswordRequest.class.php');

class User extends Record {
	protected $tableName = 'usr_users';

	protected $config = array(
		'id'		=> array(),
		'email' 	=> array(),
		'password'	=> array(),
		'created' 	=> array(
			'writability' => self::NONWRITTEN,
		),
		'updated' 	=> array(
			'writability' => self::READONLY,
		),
		'deleted' 	=> array(),		
		'admin' 	=> array(),		
		'accepte_user' 	=> array(),		
	);
	
	protected $hasOneConfig = array(
		'passwordrequest' => array(
			'class' => 'UserPasswordRequest',
			'local' => 'id',
			'foreign' => 'user_id',
		),
		'useractivation' => array(
			'class' => 'UserActivation',
			'local'	=> 'id',
			'foreign' => 'user_id',
		),
	);

	protected $data = array (
		'admin' => 0,
		'accepte_user' => 0
	);
	
	public function init() {
		$this->registerPlugin('Updatable');
		$this->registerPlugin('SoftDeletable');
	}


	/*
		Property: $loggedIn
		Stores whether the user is logged in or not.
	*/
	protected $loggedIn = false;

	/*
		Property: $cookieName
		Gives the name of the cookie that stores data to log the user in.
	*/
	protected $cookieName = 'user';

	/*
		Property: $cookieDuration
		Gives the time (in seconds) the cookie given in <$cookieName> will last.
	*/
	protected $cookieDuration = 1209600; //60*60*24*14;

	/*
		Method: login
		Attempts to log in a user.
		If logging in is successful, the associated data will be loaded into this object. Otherwise, it
		will remain at its previous state.

		Parameters:
		$username - The username of the user
		$password - The unencrypted password of the user

		Returns:
		A boolean stating whether logging in was successful.
	*/
	public function login($email, $password) {
			$result = $this->select()
			->where('lower(email) = lower(%) AND password = %', $email, sha1($password))
			->getStatement()->fetchRow();

		if ($result) {
			$this->doLogin($result[$this->getPkColumn()]);
			return $this->loggedIn;
		}
	}

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
		$_SESSION['user_id'] = $id;		

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
		$_SESSION['user_id'] = null;
	}

	/*
		Method: cookieLogin
	  Logs the user in using the persistent login cookie.

		Returns:
		Whether login through this method was successful.
	*/
	public function cookieLogin() {
		if (!isset($_COOKIE[$this->cookieName]) || !preg_match('/^[a-z0-9]+$/i', $_COOKIE[$this->cookieName]))
			return false;

		$result = $this->select()->where('md5(%l) = %', $this->db->concat('id', '\'|\'', 'email', '\'|\'', 'created'), $_COOKIE[$this->cookieName])->getStatement()->fetchRow();

		if (!$result)
			return false;

		$this->doLogin($result['id']);
		return $this->loggedIn;
	}

	/*
		Method: removeCookie
		Removes the persistent login cookie.
	*/
	public function removeCookie($domain) {
		setcookie($this->cookieName, '', time() - 3600, '/', '.'.$domain); // TODO : write to $_COOKIE and handle setcookie globally
		unset($_COOKIE[$this->cookieName]);
	}

	/*
		Method: setCookie
	  Set the persistent login cookie.
	*/
	public function setCookie($domain) {
		if (!$this->loggedIn) return;
		$data = md5($this->id.'|'.$this->email.'|'.$this->created);
		setcookie($this->cookieName, $data, time() + $this->cookieDuration, '/', '.'.$domain); // TODO : write to $_COOKIE and handle setcookie globally
	}
	
	/*
		Method: requestNewPassword
		Starts a password reset request for the current user.

		Returns:
		A string hash that must be provided to <confirmNewPassword> to actually have the password
		reset.
	*/
	public function requestNewPassword() {
		if (!$this->id)
			throw new Exception('Attempted to request a new password for a new user');

		if (!$this->passwordrequest) {
			$this->passwordrequest = new UserPasswordRequest();
			$this->passwordrequest->user_id = $this->id;
		}

		$this->passwordrequest->generateHash();
		$this->passwordrequest->save();

		return $this->passwordrequest->hash;
	}

	/*
		Method: confirmNewPassword
		Confirms a previously requested password reset.

		Note that this method will load the user associated to the reset request.
		Also, the new password is automatically saved.

		Parameters:
		$hash - The reset request hash, which was returned from <requestNewPassword>.

		Returns:
		*false* if the request hash was not found, otherwise a string with the new password for
		the user.
	*/
	public function confirmNewPassword($hash) {
		$passwordrequest = new UserPasswordRequest();
		try {
			$passwordrequest->loadByUnique('hash',$hash);
		} catch (Accepte_Database_Record_Exception_NotFound $e) {}

		if (!$passwordrequest->user)
			return false;

		$passwordrequest->delete();
		$this->load($passwordrequest->user_id);
		$password = UserPasswordRequest::randomString(8);
		$this->password = $password;
		$this->save();

		return $password;
	}

	/*
		Creates an activation hash for the user.
		this will automatically disallow logging in for the account
	*/
	public function deactivateAccount() {
		if (!$this->useractivation) {
			$this->useractivation = new UserActivation();
			$this->useractivation->user_id = $this->id;
		}
		$this->useractivation->generateHash();
		$this->useractivation->save();

		return $this->useractivation->hash;
	}

	public function activateAccount($hash) {
		$useractivation = new UserActivation();
		try {
			$useractivation->loadByUnique('hash',$hash);
		} catch (Accepte_Database_Record_Exception_NotFound $e) { return false; }

		if (!$useractivation->user)
			return false;

		$this->load($useractivation->user_id);
		$useractivation->delete();

		return true;
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
			break;
			default:
				return parent::__get($name);
		}
	}

	/*
		Method: __set
		Magic method used to enable access to properties.

		Parameters:
		$name - The name of the property to set
		$value - The value of the property
	*/
	public function __set($name, $value) {
		switch ($name) {
			case 'password':
				$value = sha1($value);
			break;
			case 'email':
				$value = strtolower($value);
			break;
			case 'loggedIn':
				$this->loggedIn = (bool)$value;
				return;
			break;
		}
		parent::__set($name, $value);
	}
}