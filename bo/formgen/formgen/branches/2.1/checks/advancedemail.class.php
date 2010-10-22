<?php

class advencedemail extends Check {
	protected $defaultTarget = 'target';
	protected $targets = array('target' => null);
	protected $emailPattern = '/^[-a-z0-9.!#$%&\'*+\/=?^_`{}|~]+@[-a-z0-9.]+\.[a-z]{2,6}$/i';

	protected $advancedEmailPattern = '';

	public function __construct($errorMsg, InputElement $target = null) {
		parent::__construct($errorMsg, $target);
		$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
        $dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
        $atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c'.
            '\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
        $quoted_pair = '\\x5c[\\x00-\\x7f]';
        $domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";
        $quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";
        $domain_ref = $atom;
        $sub_domain = "($domain_ref|$domain_literal)";
        $word = "($atom|$quoted_string)";
        $domain = "$sub_domain(\\x2e$sub_domain)*";
        $local_part = "$word(\\x2e$word)*";
        $addr_spec = "$local_part\\x40$domain";
        $this->advancedEmailPattern = "!^$addr_spec$!";
	}

	public function getExpr() {
		return new GenericExpr(
			array($this, 'phpExpr'),
			array($this, 'jsExpr'),
			null,
			array($this, 'exGetTargets'),
			$this->targets['target']
		);
	}

	public function phpExpr($indent, $data) {
		return 'preg_match(\''.addslashes($this->advancedEmailPattern . 'u').'\', \''.$data->getPHP($indent).'\')';
	}

	public function jsExpr($indent, $data) {
		return '('.$data->getJSValue().'.search('.$this->emailPattern.')!=-1)';
	}

	public function exGetTargets($data) {
		return $data->getTargets();
	}

	public function valid($callbacks) {
		return preg_match($this->emailPattern . 'u', trim($this->targets['target']->getValue()));
	}
}

?>