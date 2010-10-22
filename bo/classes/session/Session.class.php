<?php

class Session {
	private static $started = false;
	private static $writeClosed = false;
	private static $destroyed = false;

	/**
	 * A reference to the set session save handler
	 *
	 * @var SessionSaveHandler
	 */
	private static $saveHandler = null;

	/**
	 * Constructor overriding - make sure that a developer cannot instantiate
	 */
	private function __construct() {
	}

	/**
	 * setSaveHandler() - Session Save Handler assignment
	 *
	 * @param SessionSaveHandler $saveHandler
	 * @return void
	 */
	public static function setSaveHandler(SessionSaveHandler $saveHandler) {
		session_set_save_handler(
			array(&$saveHandler, 'open'),
			array(&$saveHandler, 'close'),
			array(&$saveHandler, 'read'),
			array(&$saveHandler, 'write'),
			array(&$saveHandler, 'destroy'),
			array(&$saveHandler, 'gc')
		);
		self::$saveHandler = $saveHandler;
	}

	/**
	 * getSaveHandler() - Get the session Save Handler
	 *
	 * @return SessionSaveHandler
	 */
	public static function getSaveHandler() {
		return self::$saveHandler;
	}

	/**
	  * start() - Start the session.
	  *
	  * @throws Exception
	  * @return void
	  */
	public static function start($domain = '') {		
		session_set_cookie_params(0, '/', $domain);

		if (self::$started && self::$destroyed) {
			throw new Exception('The session was explicitly destroyed during this request, attempting to re-start is not allowed.');
		}

		if (self::$started) {
			return;
		}

		if (headers_sent($filename, $linenum)) {
			throw new Exception('Session must be started before any output has been sent to the browser; output started in {'.$filename.'}/{'.$linenum.'}');
		}

		session_start();
		self::$started = true;
	}

	/**
	 * isStarted() - convenience method to determine if the session is already started.
	 *
	 * @return bool
	 */
	public static function isStarted() {
		return self::$started;
	}

	/**
	 * getId() - get the current session id
	 *
	 * @return string
	 */
	public static function getId() {
		return session_id();
	}

	/**
	 * writeClose() - Shutdown the sesssion, close writing and detach $_SESSION from the back-end storage mechanism.
	 * This will complete the internal data transformation on this request.
	 *
	 * @param bool $readonly - OPTIONAL remove write access (i.e. throw error if Zend_Session's attempt writes)
	 * @return void
	 */
	public static function writeClose() {
		if (self::$writeClosed) {
			return;
		}

		session_write_close();
		self::$writeClosed = true;
	}

	/**
	 * destroy() - This is used to destroy session data, and optionally, the session cookie itself
	 *
	 * @param bool $remove_cookie - OPTIONAL remove session id cookie, defaults to true (remove cookie)
	 * @param bool $readonly - OPTIONAL remove write access (i.e. throw error if Zend_Session's attempt writes)
	 * @return void
	 */
	public static function destroy() {
		if (self::$destroyed) {
			return;
		}
	
		session_destroy();
		self::$destroyed = true;
	}
}