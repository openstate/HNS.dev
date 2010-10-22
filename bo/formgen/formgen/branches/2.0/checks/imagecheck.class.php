<?php

class imagecheck extends Check {
	protected $defaultTarget = 'target';
	protected $targets = array('target' => null);
	protected $mimes = array('image/jpeg', 'image/pjpeg', 'image/gif', 'image/png');
	
	public function getExpr() {
		return new GenericExpr(
			array($this, 'phpExpr'),
			array($this, 'jsExpr'),
			null,
			array($this, 'exGetTargets'),
			$this->targets['target']
		);		
	}
	
	public function setParam($name, $value, ExprParser $expr) {
		if ($name == 'mime') {				
			$this->mimes[] = $value;
		} else
			parent::setParam($name, $value, $expr);
	}
	
	public function phpExpr($indent, $data) {
		throw Exception('server::phpExpr not yet implemented.');
	}

	public function jsExpr($indent, $data) {
		return 'true';
	}
	
	public function exGetTargets($data) {
		return array();
	}
	
	public function valid($callbacks) {
		$file = $this->targets['target']->getValue();
		
		$data = array('', '', '@@NOT VALID@@');
		$data = @getimagesize($file['tmp_name']);
		$mime = image_type_to_mime_type($data[2]);		
		foreach($this->mimes as $validMime) {			
			if ($mime == $validMime) return true;			
		}
		return false;		
	}	
}