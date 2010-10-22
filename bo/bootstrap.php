<?php

include_once $_SERVER['DOCUMENT_ROOT'].'/../privates/settings.private.php';

set_include_path(
	'../library/'.PATH_SEPARATOR.
	'../classes'.PATH_SEPARATOR.
	'../classes/database'.PATH_SEPARATOR.
	get_include_path()
);

/*
	Load database class
*/
require_once 'DBs.class.php';
require_once 'record/Record.abstract.php';

/*
	swfupload
*/
if(isset($_POST['swfupload_id'])) {
	session_id($_POST['swfupload_id']);
}
/*
	Site config
*/
require_once 'Site.class.php';
$site = new Site();
$site->addSite('/^(?:(?P<subdomain>backoffice)\.)?(?P<domain>hnsdev|hns-dev|dev\.hetnieuwestemmen)\.(?P<tld>(?:nl\.)?gl|(?:nl\.)?devel|(?:nl\.)?accepteproject\.nl|(?:nl\.)?acceptelive\.nl|nl)(?::80)?(?:\/.*)?$/i', 'backoffice');

$site->process();

require_once 'session/Session.class.php';
require_once 'session/DbSessionSaveHandler.class.php';
//Start the session
Session::setSaveHandler(new DbSessionSaveHandler(DBs::inst(DBs::SYSTEM)));
Session::start('.'.$site->getTopDomain());


require_once 'Dispatcher.class.php';
require_once 'routes/DirectRoute.class.php';
require_once 'routes/NamedRoute.class.php';
require_once 'routes/AntiSpamRoute.class.php';
require_once 'Router.class.php';
require_once 'HttpRequest.class.php';
require_once 'HttpResponse.class.php';
require_once 'Layout.class.php';

// create the request and response
$request = new HttpRequest($site);
$response = new HttpResponse();

// create the dispatcher
$dispatcher = new Dispatcher('../modules', $request, $response);

//create the backoffice login plugin
require_once 'UsersBOLoginPlugin.class.php';
$dispatcher->registerPlugin(new UsersBOLoginPlugin());

// initialize the response
$helper = new ViewHelper($dispatcher);
$response->setViewHelper($helper);

// create the router
$router = new Router($site->getTopDomain());

// initialize the layout
$layout = new Layout($response->createView('Smarty'));
$layout->setTemplatePath(realpath('../templates/').'/');
$layout->setTemplate('backofficeTemplate.html');
$layout->setOuterTemplate('outerTemplates/backoffice.html');
$router->addRoute('module', new NamedRoute('/modules/:module/:controller/:action/*'));
$router->addRoute('named', new NamedRoute('/:controller/:action/*', array('module' => 'admin'), array('controller' => '^(?!modules).*$')));

$response->setLayout($layout);

try {
	$dispatcher->setRouter($router);
	$dispatcher->dispatch();
} catch (Exception $e) {
	echo '<pre>'.$e->__toString().'</pre>';
}

?>
