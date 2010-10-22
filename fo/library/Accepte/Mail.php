<?php

/*
	Mail class which sets some basic mail settings
*/
require_once 'Zend/Mail.php';
require_once 'view/SmartyView.class.php';

class Accepte_Mail extends Zend_Mail	
{
	protected $_template = '';
	protected $_view = null;	
	protected $_baseUrl;

	protected $_fromAddress = array('no-reply@framework.gl', 'Framework.gl');
	protected $_title ='Framework';

	public function __construct($view = null) {
		parent::__construct('utf-8');				

		//project specifics
		$this->_template = $_SERVER['DOCUMENT_ROOT'] . '/../templates/email/default.html';
		$this->_view = $view;				
	}

	public function setBaseUrl($baseUrl) {
		$this->_baseUrl = $baseUrl;
	}
	
	public function setTemplate($template) {		
		$this->_template = $template;
	}

	public function setBodyHtml($html, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE) {		
		if ($this->_view !== null) {			
			$this->_view->site_domain = $this->_baseUrl;
			$this->_view->site_title = $this->_title;
			$this->_view->content = $html;
			$this->_view->setTemplatePath(dirname($this->_template));
			ob_start();
				$this->_view->render(basename($this->_template));
			$html = ob_get_clean();
		}		
		return parent::setBodyHtml($html, $charset, $encoding);
	}

	public function send() {
		parent::setFrom($this->_fromAddress[0], $this->_fromAddress[1]);
		parent::setReturnPath($this->_fromAddress[0]);
		require_once 'Zend/Mail/Transport/Sendmail.php';
		return parent::send(new Zend_Mail_Transport_Sendmail('-f'.$this->getReturnPath()));
	}

	public function setFrom($email, $name = '') {
		$this->_fromAddress = array($email, $name);
		return $this;
	}
}