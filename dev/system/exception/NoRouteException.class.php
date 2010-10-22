<?php

class NoRouteException extends Exception {
	
	protected $returnUrl = null;
	
	function __construct($message = null, $returnUrl = null, $code = null ) {
		parent::__construct($message, $code);
		$this->returnUrl = $returnUrl;	
	}
	
	public function getReturnUrl() {
		return $this->returnUrl;
	}
}

?>