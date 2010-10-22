<?php
	require_once(dirname(__FILE__).'/cron.include.php');
	require_once('load.include.php');

	try {
		$load = load();
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/../privates/current_load.private.php', <<<EOF
<?php

	define('CURRENT_LOAD', $load);

?>
EOF
		);
	} catch (Exception $e) {
		mail_exception($e);
	}
?>