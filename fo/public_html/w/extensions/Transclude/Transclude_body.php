<?php

class Transclude extends IncludableSpecialPage {
	function __construct() {
		parent::__construct( 'Transclude', '', false);
	}

	function getDescription() {
		return 'Transclude';
	}

	function execute( $par ) {
		global $wgServer, $wgOut;
		
		$this->setHeaders();

		$ch = curl_init($wgServer.'/'.$par.(strpos($par, '?') === false ? '?' : '&').'transclude=true');
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIE, implode('; ', array_map(create_function('$k,$v', 'return urlencode($k)."=".urlencode($v);'), array_keys($_COOKIE), $_COOKIE)));
		$output = curl_exec($ch);
		
		$wgOut->addHTML($output);
	}
}
