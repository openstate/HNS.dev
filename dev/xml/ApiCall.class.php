<?php

class ApiCall {
	public function processGet($get) {
		/* authentication */
		
		//header('Content-Type: text/xml');
		require_once('XmlQuery.class.php');
		try {
			if ($_SERVER['CONTENT_TYPE'] != 'text/xml')
				throw new Exception('Unsupported content type');
			$query = XmlQuery::parse(file_get_contents('php://input'));
			$content = $query->execute();
		} catch (Exception $e) {
			$error = $e->__toString();
		}

		$xml = new SimpleXmlElement('<?xml version="1.0" encoding="UTF-8" ?><query></query>');
		if (@$error) $xml->addChild('error', htmlspecialchars($error));
		elseif (@$content) {
			foreach ($content as $tuple) {
				@list($name, $item, $string) = $tuple;
				$node = $xml->addChild($name, $string);
				foreach ($item as $key => $value) {
					$call = $key == 'id' ? 'addAttribute' : 'addChild';
					$node->$call($key, $value);
				}
			}
		}
		echo (str_replace('><', ">\n<", $xml->asXml()));
	}
}

?>