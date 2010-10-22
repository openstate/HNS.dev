<?php
	require(dirname(__FILE__).'/cron.include.php');

	try {
		DBs::inst(DBs::SYSTEM)->query('SELECT decay_tags()')->fetchCell();
	} catch (Exception $e) {
		mail_exception($e);
	}
?>