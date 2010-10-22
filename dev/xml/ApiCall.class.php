<?php

class ApiCall {
	public static $cacheDir = '/cache/queries/';
	public static $cacheDuration = 600;
	
	protected $xml;

	protected function createXml($xml, $array) {
		foreach ($array as $tuple) {
			@list($name, $item, $string) = $tuple;
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
	}

	public function processGet($get) {
		/* authentication */
		
		require_once('XmlQuery.class.php');
		try {
			if ($_SERVER['CONTENT_TYPE'] != 'text/xml')
				throw new Exception('Unsupported content type');
			DataStore::set('api_developer', 1);
			$input = file_get_contents('php://input');
			$hash = md5($input);
			$cacheFile = $_SERVER['DOCUMENT_ROOT'].'/../'.self::$cacheDir.'/'.$hash.'.xml';
			if (false && file_exists($cacheFile) && filemtime($cacheFile) >= time() - self::$cacheDuration) {
				$this->xml = file_get_contents($cacheFile);
				return;
			}
			$parser = XmlQuery::parse($input);
			$query = $parser->parseXml();
			if (is_string($query)) {
				$this->xml = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/../'.self::$cacheDir.'/'.$query.'.xml');
				return;
			}
			$selectQuery = $query instanceof SelectQuery;
			$content = $query->execute();
		} catch (Exception $e) {
			$error = $e->__toString();
		}

		$xml = new SimpleXmlElement('<?xml version="1.0" encoding="UTF-8" ?><'.$parser->rootTag.'></'.$parser->rootTag.'>');
		if (@$error) $xml->addChild('error', htmlspecialchars($error));
		elseif (@$content) {
			$this->createXml($xml, $content);
			if ($selectQuery) {
				//$xml->addAttribute('hash', $hash);
				file_put_contents($cacheFile, $xml->asXml());
			}
		}
		$this->xml = $xml->asXml();
	}
	
	public function show() {
		//header('Content-Type: text/xml');
		echo (str_replace('><', ">\n<", $this->xml));
	}
}

?>