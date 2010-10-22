<?php
	require_once(dirname(__FILE__).'/../includes/prequel.include.php');

	require_once('Dispatcher.class.php');
	Dispatcher::inst()->dispatch();
?>