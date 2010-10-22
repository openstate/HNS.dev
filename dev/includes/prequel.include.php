<?php
	// Start output buffering
	ob_start();

	// Find modules
	$cwd = getcwd();
	chdir($_SERVER['DOCUMENT_ROOT'].'/../');
	$dirs = glob('*', GLOB_ONLYDIR);

	// Set include path
	$includePath = array('includes/', 'privates/');

	foreach($dirs as $dir) {
		if (file_exists($file = $dir.'/'.$dir.'.includepath.php')) {
			$includePath = array_merge($includePath, require(getcwd().'/'.$file));
		}
	}

	$includePath = array_map('realpath', $includePath);
	array_unshift($includePath, get_include_path());

	set_include_path(implode(PATH_SEPARATOR, $includePath));

	// Include global settings
	require_once('settings.include.php');

	// Include private settings
	require_once('settings.private.php');

	// Include default classes
	foreach($dirs as $dir) {
		if (file_exists($file = $dir.'/'.$dir.'.requires.php'))
			require_once(getcwd().'/'.$file);
	}

	// Restore working dir
	chdir($cwd);
?>