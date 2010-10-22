<?php

	$namespaces = null;

	function rewrite_class($match) {
		global $namespaces;
		$new = false;
		if (preg_match('!^/wiki/(.+)$!', $match[2] == '?new?' ? $match[4] : $match[2], $href)) {
			$list = explode(':', $href[1], 2);
			if (count($list) > 1 && !$namespaces) {
				$namespaces = require('namespaces.include.php');
				$ns = $namespaces[$list[0]];
				$page = $list[1];
			} else {
				$ns = 0;
				$page = $list[0];
			}
			$new = !((boolean) DBs::inst(DBs::SYSTEM)->query('
				SELECT 1 FROM mediawiki.page
				WHERE page_namespace = % AND page_title = %',
				$ns, $page)->fetchCell());
		}
		if ($match[2] == '?new?')
			return $match[1].($new ? 'new' : '').$match[3].$match[4].$match[5];
		else
			return $match[1].$match[2].$match[3].($new ? 'new' : '').$match[5];
	}

	function smarty_outputfilter_wiki_links($output, &$smarty) {
		return str_replace(' class=""', '', preg_replace_callback(array(
					'/(<a\s[^>]*?href=")([^"]+)("\s[^>]*?)(\?new\?)([^>]*?>)/i',
					'/(<a\s[^>]*?)(\?new\?)([^>]*?href=")([^"]+)("[^>]*? >)/i',
				), 'rewrite_class', $output));
	}

?>