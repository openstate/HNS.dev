<?php
	$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__).'/../../public_html/';
	$_SERVER['CRON'] = true;

	require_once($_SERVER['DOCUMENT_ROOT'].'/../includes/prequel.include.php');
	
	function mail_exception($ex) {
		// pass
	}
?>