<?php

require_once('Rights.class.php');

class ApiCall {
	public static $cacheDir = '/cache/queries/';
	public static $cacheDuration = 600;
	
	protected $xml;

	/* Generate XML based on the result of the executed query */
	protected function createXml($xml, $array) {
		$result = null;
		foreach ($array as $tuple) {
			@list($name, $item, $string) = $tuple;
			if ($name == 'sql') {
				/* The sql tag is suppressed on live, and stored in the log */
				$result = $string;
				if (!DEVELOPER) continue;
			}
			/* Generate a node with text content if any was supplied */
			$node = $xml->addChild($name, $string);
			$nodes = array();
			$defer = array();
			
			foreach ($item as $key => $value) {
				/* If the value is a string, we have a terminal node */
				if (!is_array($value)) {
					if (strpos($key, '/') === false) {
						/* Regular node: ids are added as attribute, other keys as child (# forces attribute) */
						$call = $key == 'id' || $key[0] == '#' ? 'addAttribute' : 'addChild';
						if ($key[0] == '#') $key = substr($key, 1);
						$nodes[$key] = $node->$call($key, $value);
						
						/* If any deferred attributes (see below) are waiting, add them */
						if (array_key_exists($key, $defer))
							foreach($defer[$key] as $att => $value)
								$nodes[$key]->addAttribute($att, $value);
					} else {
						/* The string contains a slash, so it's an attribute for an existing tag */
						list($key, $att) = explode('/', $key, 2);
						if (array_key_exists($key, $nodes))
							/* The tag exists already, add it */
							$nodes[$key]->addAttribute($att, $value);
						else
							/* The tag doesn't exist yet, defer adding it (see above) */
							$defer[$key][$att] = $value;
					}
				} else
					/* The value is an array, create the structure recursively */
					$this->createXml($node->addChild($key), $value);
			}
		}
		/* Return any sql tag value */
		return $result;
	}
	
	/* Log the api call */
	protected function log($xmlIn, $xmlOut, $hash, $sql, $queries, $time, $ex) {
		DBs::inst(DBs::LOGGING)->query(
			'INSERT INTO api_log (user_id, hash, xml_in, xml_out, sql, queries, time, exception) VALUES (%, %, %, %, %, %, %, %)',
			DataStore::exists('api_user') ? DataStore::get('api_user')->id : null,
			$hash, $xmlIn, str_replace('><', ">\n<", $xmlOut), $sql, $queries, $time, $ex ? $ex->__toString() : null
		);
	}

	public function processGet($get) {
		try {
			require_once('XmlQuery.class.php');
			
			/* Fetch xml input */
			$input = file_get_contents('php://input');
			
			/* If the username or key isn't provided, terminate */
			if (!@$get['user'] || !@$get['key'])
				throw new ParseException('Required parameters missing');
				
			/* Content type should be text/xml */
			if ($_SERVER['CONTENT_TYPE'] != 'text/xml')
				throw new ParseException('Unsupported content type');
			
			/* Find user */
			require_once('User.class.php');
			$user = new User();
			try {
				$user->loadByUnique('name', $get['user']);
			} catch (RecordException $e) {
				throw new ParseException('User "'.$get['user'].'" not found');
			}
			
			if ($user->ip) {
				/* User has an associated ip check, verify the current ip matches */
				list($ip, $mask) = explode('/', $user->ip);
				if ($mask === null) $mask = 32;
				
				/* Flip the mask so we can shift the integer representations of the ips */
				$mask = 32 - $mask;
				$ip = ip2long($ip) >> $mask;
				$remote = ip2long($_SERVER['REMOTE_ADDR']) >> $mask;
				
				/* If the mask is 32 (ie, all ips match, the shift may fail due to sign extension
				   so treat it as a special case */
				if ($mask < 32 && $ip != $remote)
					throw new ParseException('Invalid remote address');
			}
			
			if (trim(reset(explode("\n", $user->key)), "- \t\r\n") == 'BEGIN PUBLIC KEY') {
				/* Account has RSA key associated with it, verify the signature sent */
				$pub = openssl_get_publickey($user->key);
				$sig = pack('H*', $get['key']);
				$valid = openssl_verify($input, $sig, $pub);
				openssl_free_key($pub);
				if (!$valid)
					throw new ParseException('Invalid signature');
			} else {
				/* Account has basic key verification, check it */
				if ($user->key != $get['key'])
					throw new ParseException('Invalid key');
			}
			
			/* If the user has exceeded their maximum rate, block them */
			if ($user->hits() >= $user->max_rate && $user->max_rate !== null)
				throw new ParseException('Too many hits');
			
			/* If the system load exceeds the maximum load for this user, block them */
			require_once('current_load.private.php');
			if (CURRENT_LOAD >= $user->max_load && $user->max_load !== null)
				throw new ParseException('Server busy');
		
			/* Store the user for external use and get a hash of the input value */
			DataStore::set('api_user', $user);
			$hash = sha1($input);
			
			/* Verify whether the query is already cached */
			$cacheFile = $_SERVER['DOCUMENT_ROOT'].'/../'.self::$cacheDir.'/'.$hash.'.'.$user->id.'.xml';
			if (0) {
			if (file_exists($cacheFile) && filemtime($cacheFile) >= time() - self::$cacheDuration) {
				/* Cache hit found, return it */
				$this->xml = file_get_contents($cacheFile);
				$this->log($input, $this->xml, $hash, null, null, null, null);
				return;
			}
			}
				
			/* Create parser and parse xml */
			$parser = XmlQuery::parse($input);
			$query = $parser->parseXml();
			
			/* If the query returns a string, it's a cache request, so return that file */
			if (is_string($query)) {
				$this->xml = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/../'.self::$cacheDir.'/'.$query.'.'.$user->id.'.xml');
				$this->log($input, $this->xml, $hash, null, null, null, null);
				return;
			}
			
			/* Add a hit for this user (for rate calculations) */
			$user->hit();
			
			/* Execute the query */
			$content = $query->execute();
			$selectQuery = $query instanceof SelectQuery;

			/* __toString can't throw exceptions so use this as a workaround */
			if (DataStore::exists('query_exception'))
				throw DataStore::get('query_exception');
		} catch (Exception $e) {
			/* Error, get the error message and store the exception */
			$ex = $e;

			/* Reraise if developer */
			if (DEVELOPER)
				throw $e;

			/* Format error message */
			if ($e instanceof RecordException || $e instanceof ParseException)
				$error = $e->getMessage();
			elseif ($e instanceof DatabaseQueryException)
				$error = 'Unspecified query error';
			else
				$error = 'Internal server error';
		}

		/* Find the root tag for the return xml */
		$rootTag = @$parser ? $parser->rootTag : 'query';
		
		/* Generate the return xml */
		$xml = new SimpleXmlElement('<?xml version="1.0" encoding="UTF-8" ?><'.$rootTag.'></'.$rootTag.'>');
		if (@$error) {
			/* Error, add a tag with the error message */
			$xml->addChild('error', htmlspecialchars($error));
		} elseif (@$content) {
			/* Build the xml tree */
			$sql = $this->createXml($xml, $content);
			
			/* If this is a select query, add the hash as attribute and store the result in the cache */
			if ($selectQuery) {
				$xml->addAttribute('hash', $hash);
				file_put_contents($cacheFile, $xml->asXml());
			}
		}
		
		/* Store the xml */
		$this->xml = $xml->asXml();
		$queries = DBs::inst(DBs::SYSTEM)->getLastQuery(-1);
		$time = array_sum(array_map(create_function('$r', 'return $r[1];'), $queries));
		$this->log($input, $this->xml, @$selectQuery ? $hash : null, @$sql, count($queries), $time, @$ex);
	}
	
	public function show() {
		/* Output the xml */
		header('Content-Type: text/xml');
		echo (str_replace('><', ">\n<", $this->xml));
	}
}

?>