<?php

require_once('Rights.class.php');

class ApiCall {
	public static $cacheDir = '/cache/queries/';
	public static $cacheDuration = 600;
	
	protected $xml;

	protected function createXml($xml, $array) {
		$result = null;
		foreach ($array as $tuple) {
			@list($name, $item, $string) = $tuple;
			if ($name == 'sql') {
				$result = $string;
				continue;
			}
			$node = $xml->addChild($name, $string);
			$nodes = array();
			$defer = array();
			foreach ($item as $key => $value) {
				if (!is_array($value)) {
					if (strpos($key, '/') === false) {
						$call = $key == 'id' ? 'addAttribute' : 'addChild';
						$nodes[$key] = $node->$call($key, $value);
						if (array_key_exists($key, $defer))
							foreach($defer[$key] as $att => $value)
								$nodes[$key]->addAttribute($att, $value);
					} else {
						list($key, $att) = explode('/', $key, 2);
						if (array_key_exists($key, $nodes))
							$nodes[$key]->addAttribute($att, $value);
						else
							$defer[$key][$att] = $value;
					}
				} else
					$this->createXml($node->addChild($key), $value);
			}
		}
		return $result;
	}
	
	protected function log($xmlIn, $xmlOut, $hash, $sql, $ex) {
		DBs::inst(DBs::LOGGING)->query(
			'INSERT INTO api_log (user_id, hash, xml_in, xml_out, sql, exception) VALUES (%, %, %, %, %, %)',
			DataStore::exists('api_user') ? DataStore::get('api_user')->id : null,
			$hash, $xmlIn, str_replace('><', ">\n<", $xmlOut), $sql, $ex ? $ex->__toString() : null
		);
	}

	public function processGet($get) {
		try {
			require_once('XmlQuery.class.php');
			if (!@$get['user'] || !@$get['key'])
				throw new Exception('Required parameters missing');
				
			require_once('User.class.php');
			$user = new User();
			$user->loadByUnique('name', $get['user']);
			
			if ($user->ip) {
				list($ip, $mask) = explode('/', $user->ip);
				if ($mask === null) $mask = 32;
				$mask = 32 - $mask;
				$ip = ip2long($ip) >> $mask;
				$remote = ip2long($_SERVER['REMOTE_ADDR']) >> $mask;
				if ($mask < 32 && $ip != $remote)
					throw new Exception('Invalid remote address');
			}
			
			if ($_SERVER['CONTENT_TYPE'] != 'text/xml')
				throw new Exception('Unsupported content type');
			
			$input = file_get_contents('php://input');
			
			if (trim(reset(explode("\n", $user->key)), "- \t\n") == 'BEGIN PUBLIC KEY') {
				$pub = openssl_get_publickey($user->key);
				$sig = pack('H*', $get['key']);
				$valid = openssl_verify($input, $sig, $pub);
				openssl_free_key($pub);
				if (!$valid)
					throw new Exception('Invalid signature');
			} else {
				if ($user->key != $get['key'])
					throw new Exception('Invalid key');
			}
			
			if ($user->hits() >= $user->max_rate)
				throw new Exception('Too many hits');
				
			require_once('current_load.private.php');
			if (CURRENT_LOAD >= $user->max_load)
				throw new Exception('Server busy');
		
			DataStore::set('api_user', $user);
			$hash = sha1($user->id.'||'.$input);
			$cacheFile = $_SERVER['DOCUMENT_ROOT'].'/../'.self::$cacheDir.'/'.$hash.'.xml';
			if (file_exists($cacheFile) && filemtime($cacheFile) >= time() - self::$cacheDuration) {
				$this->xml = file_get_contents($cacheFile);
				return;
			}
			$parser = XmlQuery::parse($input);
			$query = $parser->parseXml();
			if (is_string($query)) {
				$this->xml = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/../'.self::$cacheDir.'/'.$query.'.xml');
				return;
			}
			$user->hit();
			$content = $query->execute();
			$selectQuery = $query instanceof SelectQuery;
		} catch (Exception $e) {
			$ex = $e;
			$error = $e->getMessage();
		}

		$rootTag = @$parser ? $parser->rootTag : 'query';
		$xml = new SimpleXmlElement('<?xml version="1.0" encoding="UTF-8" ?><'.$rootTag.'></'.$rootTag.'>');
		if (@$error) {
			$xml->addChild('error', htmlspecialchars($error));
		} elseif (@$content) {
			$sql = $this->createXml($xml, $content);
			if ($selectQuery) {
				$xml->addAttribute('hash', $hash);
				file_put_contents($cacheFile, $xml->asXml());
			}
		}
		$this->xml = $xml->asXml();
		$this->log($input, $this->xml, @$selectQuery ? $hash : null, @$sql, @$ex);
	}
	
	public function show() {
		header('Content-Type: text/xml');
		echo (str_replace('><', ">\n<", $this->xml));
	}
}

?>