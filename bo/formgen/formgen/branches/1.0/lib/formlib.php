<?php

function stripAttribs($html) {
	if (substr(strtolower($html[0]), 0, 2) == '<a') {
		// Filter out javascript: links
		$html[0] = preg_replace('/\bhref=([\'"])javascript:.*?\1/s', '', $html[0]);
	}
	return preg_replace('/\bon[a-z]+\s*=\s*(["\']).*?\1/is', '', $html[0]);
}

function safeHtml($html) {
	// clear script, object & embed tags.
	$html = preg_replace('!<(script|object|embed)[^>]*>.*?</\1>!is', '', $html);
	// Remove potentially dangerous attributes: all those starting with 'on'
	$html = preg_replace_callback('!<[a-zA-Z]+\s+(?>[a-zA-Z]+(?>\s*=\s*(["\']).*?\1\s*)?)+(\s*/)?>!s', 'stripAttribs', $html);
	return $html;
}

?>
