<?php

require_once 'Request.abstract.php';
require_once 'Response.abstract.php';

// Abstract class for X plugins

class PluginException extends Exception {}

abstract class Plugin {
	public function routeStartup(Request $request, Response $response) {}
	public function routeShutdown(Request $request, Response $response) {}
	public function dispatchLoopStartup(Request $request, Response $response) {}
	public function preDispatch(Request $request, Response $response) {}
	public function postDispatch(Request $request, Response $response) {}
	public function dispatchLoopShutdown(Request $request, Response $response) {}
	public function exception(Request $request, Response $response, Exception $exception) {}
}