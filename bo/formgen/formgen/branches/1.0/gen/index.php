<?php
	//set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__));
	set_include_path(dirname(__FILE__));
	
	set_time_limit(0); //no timeout

	if (php_sapi_name()!='cli') {
		define('HTMLOUTPUT', true);
/*
	$settings = array(
		'baseDir' => 'c:/localhost/FormGen/test/testSrc/',
		'fileMask' => '*.form',
		'templateDir' => 'c:/localhost/FormGen/gen/Templates/',  // The php/js templates
		'classDir' => '../class',
		'className' => '%O%AForm',
		'htmlDir'  => '../html',
		'htmlHeadDir'  => '../header',
		'actionDir'  => '../action',
		'actionFiles' => array('_FormDisplayAction.php' => '%O%A.php')
	);

*/
		if (!isset($_GET['settings'])) {
			die('Settings file not given.');
		} else
			$settingsFile = $_GET['settings'];
	} else {
		define('HTMLOUTPUT', false);
		// Load settings file from command line parameter
		if ($_SERVER['argc']<2)
			die('Settings file not given.');
		else
			$settingsFile = $_SERVER['argv'][1];
	}

	if (HTMLOUTPUT) {
?>
<html>
<head>
<style type="text/css">
.success { color: #00C000; }
.failure { color: #FF0000; }
.error {
	border: 1px solid black;
	padding: 3px;
	line-height: 200%;
}
.error .msg {
	background: #DDD;
	padding: 0px 3px;
}
</style>
</head>
<body>

<?php
	}

	require_once('DirTraverser.class.php');
	require_once($settingsFile);
	$errormsgCustom = $errormsg;

	$t = new DirTraverser($settings, dirname(realpath($settingsFile)));

	$errormsg = array_merge($errormsg, $errormsgCustom);

	$t->traverse();

	if (HTMLOUTPUT)
		echo "<body>\n</html>";
?>