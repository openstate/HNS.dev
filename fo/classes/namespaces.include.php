<?php

class NamespaceParser {
	protected static $languages = array('nl');

	public static function parse() {
		$w = $_SERVER['DOCUMENT_ROOT'].'/w/';
		
		$def = array();
		$ns = array();
		$project = 'Project';

		$defines = explode("\n", file_get_contents($w.'/includes/Defines.php'));
		foreach ($defines as $line) {
			if (preg_match('/define\(\s*\'(NS_[A-Z0-9_]+)\'\s*,\s*(-?[0-9]+)\s*\);/', $line, $match))
				$def[$match[1]] = $match[2];
		}

		$namespace = explode("\n", file_get_contents($w.'/includes/Namespace.php'));
		foreach ($namespace as $line) {
			if (preg_match('/\s(NS_[A-Z0-9_]+)\s*=>\s*\'([^\']+)\'/', $line, $match))
				$ns[$match[2]] = $def[$match[1]];
		}

		$setup = explode("\n", file_get_contents($w.'/includes/Setup.php'));
		foreach ($setup as $line) {
			if (preg_match('/\$wgNamespaceAliases\[\'([^\']+)\'\]\s*=\s*(NS_[A-Z0-9]+)/', $line, $match))
				$ns[$match[1]] = $def[$match[2]];
		}
		
		foreach (self::$languages as $lang) {
			$messages = explode("\n", file_get_contents($w.'/languages/messages/Messages'.ucfirst(strtolower($lang)).'.php'));
			foreach ($messages as $line) {
				if (preg_match('/\s(NS_[A-Z0-9_]+)\s*=>\s*\'([^\']+)\'/', $line, $match))
					$ns[$match[2]] = $def[$match[1]];
				if (preg_match('/\s\'([^\']+)\'\s*=>\s*(NS_[A-Z0-9_]+)/', $line, $match))
					$ns[$match[1]] = $def[$match[2]];
			}
		}

		$local = explode("\n", file_get_contents($w.'/LocalSettings.php'));
		foreach ($local as $line) {
			if (preg_match('/define\(\s*\'(NS_[A-Z0-9_]+)\'\s*,\s*([0-9]+)\s*\);/', $line, $match))
				$def[$match[1]] = $match[2];
			if (preg_match('/define\(\s*"(NS_[A-Z0-9_]+)"\s*,\s*([0-9]+)\s*\);/', $line, $match))
				$def[$match[1]] = $match[2];
			if (preg_match('/\$wgExtraNamespaces\[([0-9]+)]\s*=\s*\'([^\']+)\'/', $line, $match))
				$ns[$match[2]] = $match[1];
			if (preg_match('/\$wgExtraNamespaces\[([0-9]+)]\s*=\s*"([^"]+)"/', $line, $match))
				$ns[$match[2]] = $match[1];
			if (preg_match('/\$wgExtraNamespaces\[(NS_[A-Z0-9_]+)]\s*=\s*\'([^\']+)\'/', $line, $match))
				$ns[$match[2]] = $def[$match[1]];
			if (preg_match('/\$wgExtraNamespaces\[(NS_[A-Z0-9_]+)]\s*=\s*"([^"]+)"/', $line, $match))
				$ns[$match[2]] = $def[$match[1]];
			if (preg_match('/\$wgSitename\s*=\s*\'([^\']+)\'/', $line, $match))
				$project = $match[1];
			if (preg_match('/\$wgSitename\s*=\s*"([^"]+)"/', $line, $match))
				$project = $match[1];
		}
		
		foreach ($ns as $key => $value) {
			if (preg_match('/\$1/', $key)) {
				unset($ns[$key]);
				$key = str_replace('$1', $project, $key);
				$ns[$key] = $value;
			}
		}
		
		$ns[$project] = $def['NS_PROJECT'];
		$ns[$project.'_talk'] = $def['NS_PROJECT_TALK'];
		
		$file = '<?php'."\n".'return array('."\n";
		foreach ($ns as $key => $value)
			$file .= "\t".'\''.$key.'\' => '.$value.",\n";
		$file .= ');'."\n".'?>';
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/../files/namespaces.php', $file);
	}
}

if (filemtime($_SERVER['DOCUMENT_ROOT'].'/../files/namespaces.php') < filemtime($_SERVER['DOCUMENT_ROOT'].'/w/LocalSettings.php'))
	NamespaceParser::parse();
return require($_SERVER['DOCUMENT_ROOT'].'/../files/namespaces.php');

?>