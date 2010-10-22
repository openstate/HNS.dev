<?php
	//require_once($_SERVER['DOCUMENT_ROOT'].'../includes/prequel.inc.php');
	require_once('../../smarty/CustomSmarty.class.php');
	require_once('../class/{processorclass}.class.php');

	$smarty = new CustomSmarty('../template/template.html');

	$form = new {processorclass}();
	if (isset($_POST) && !empty($_POST)) {
		$form->processPost($_POST);
	}

	$form->show($smarty);
?>