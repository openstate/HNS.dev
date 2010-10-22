<?php

require_once 'plugin/Plugin.abstract.php';

class UsersBOLoginPlugin extends Plugin {
	public function preDispatch(Request $request, Response $response) {
		$siteName = $request->getSite()->getSiteName();

		if ($siteName == 'backoffice' && !($request->user->admin || $request->user->accepte_user)) {
			$request->getDestination()->fromUrlString('/users/index/login/');
		}
	}
}