<?php

class ApiCall {
	public function processGet($get) {
		/* authentication */
		
		require_once('XmlQuery.class.php');
		try {
			if ($_SERVER['CONTENT_TYPE'] != 'text/xml')
				throw new Exception('Unsupported content type');
			$query = XmlQuery::parse(file_get_contents('php://input'));
			$content = $query->toSql();
		} catch (Exception $e) {
			$error = $e->toString();
		}

		header('Content-Type: text/xml');
		$xml = new SimpleXmlElement('<?xml version="1.0" encoding="UTF-8" ?><query></query>');
		if (@$content) $xml->addChild('content', htmlspecialchars($content));
		if (@$error) $xml->addChild('error', htmlspecialchars($error));
		echo ($xml->asXml());
	}
}

?>