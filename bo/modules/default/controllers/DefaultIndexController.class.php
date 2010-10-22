<?php

require_once 'Controller.abstract.php';

class DefaultIndexController extends Controller {
	
	public function indexAction() {		
		$this->view->render('index/index.html');
	}
		
	/**
	 * Access via /images/filler.gif for spam bot checking
	 *
	 * @return void
	 * @author Harro
	 **/
	public function antispamAction() {
		$_SESSION['spambot'] = 'no';
		Session::writeClose();

		header('Content-type: image/gif');

		header('ETag: PUB'.time());
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()-10).' GMT');
		header('Expires: '.gmdate('D, d M Y H:i:s', time() + 5).' GMT');
		header('Pragma: no-cache');
		header('Cache-Control: max-age=1, s-maxage=1, no-cache, must-revalidate');
		session_cache_limiter('nocache');
	
		echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
		die;
	}
	
}
