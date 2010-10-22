<?php

class UserExtended extends Record {
	protected $tableName = 'usr_extended';
	
	protected $config = array(
		'id'						=> array(),
		'user_id'					=> array(),
		'photo'						=> array(),
		'organization'				=> array(),
		'position'					=> array(),
		'postalcode'				=> array(),
		'phone'						=> array(),
		'twitter'					=> array(),
		'linkedin'					=> array(),
		'skype'						=> array(),
		'shortbio'					=> array(),
		'accept_terms'				=> array(),
		'rights_read' 				=> array(),
		'rights_write' 				=> array(),
	);
	
	protected $hasOneConfig = array(
		'user' => array(
			'class' => 'User',
			'local' => 'user_id',
			'foreign' => 'user_id',
			)
		);
	
	public function init() {
		$this->registerPlugin('Objectable', array(
			'photo' => array(
				'type' => 'Image',
				'args' => array(
					'path' => '/assets/files/images/',
					'default' => '_logo.gif',
					'throw_exception' => true,
				),
			)
		));	
	}
		
	public function getWikiTitle() {
		return 'User:'.$this->user->user_name;
	}
			
	public function getWikiContent() {
		$strings = array(
			'name', 'email', 'photo', 'organization', 'shortbio', 'postalcode', 'phone', 'twitter', 'skype', 'change'
		);

		$tr = new GettextPO(dirname(__FILE__).'/../locales/en/users.po');
		foreach ($strings as $s)
			$$s = $tr->getMsgstr('user.'.$s);
			
		$user = $this->user->user_name;
		$photo = $this->photo->getThumbnail(200);
		
		$server = 'http'.(@$_SERVER['HTTPS'] ? 's' : '').'://'.$_SERVER['HTTP_HOST'];
		
		$wiki = '';
		if ($photo) {
			$wiki .= "<div style=\"float:right;\">\n";
			$wiki .= "<div style=\"margin: 5px; padding: 5px; border: 1px solid black;\">{$server}$photo</div>\n\n";
			$wiki .= "</div>\n\n";
		}
		$wiki .= "<guard user=\"".htmlspecialchars($user)."\"><div style=\"float: right;\">&#91;[[Redirect:/modules/users/index/change/{$this->getPk()}/|$change]]]</div>\n\n</guard>";
		$wiki .= "* '''$name''': {$this->user->user_real_name}\n";
		$wiki .= "* '''$email''': {$this->user->user_email}\n";
		$wiki .= "* '''$organization''': {$this->organization}\n";
		$wiki .= "* '''$postalcode''': {$this->postalcode}\n";
		$wiki .= "* '''$phone''': {$this->phone}\n";
		$wiki .= "* '''$twitter''': {$this->twitter}\n";
		$wiki .= "* '''$skype''': {$this->skype}\n\n";
		$wiki .= "=== $shortbio ===\n\n".str_replace("\n", "\n\n", trim($this->shortbio))."\n\n";
		$wiki .= "[[Category:Users|{$this->user->user_name}]]\n";
		
		return $wiki;
	}

}