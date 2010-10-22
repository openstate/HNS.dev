<?php

class ErrorMsg {
	protected $start, $length;
	protected $name = false;
	protected $uniqueName = '';
	protected $checks = array();
	protected $tplVariation = '';

	protected $subId = 0;

	protected static $autoName = 0;

	public function __construct($start, $length, $name = null) {
		$this->start = $start;

		if ($name === null) {
			$this->length = strlen($length);
			if (!preg_match('/^{#error((:([a-zA-Z0-9]*))*)}$/', $length, $match))
				throw new ParseException('Invalid error tag: '.$length);
			if (isset($match[1]) && $match[1]) {
				$params = array_slice(explode(':', $match[1]), 1);
				if (isset($params[0]))
					$this->name = $params[0];
				if (isset($params[1]))
					$this->tplVariation = $params[1];
			}
		} else {
			$this->length = $length;
			$this->name = $name;
		}

		if (!$this->name) {
			$this->name = '__error'.self::$autoName;
			$this->uniqueName = $this->name;
		} else {
			$this->uniqueName = $this->name.self::$autoName;
		}
		self::$autoName++;
	}

	public function setTplVariation($tpl) {
		$this->tplVariation = $tpl;
	}

	public function offset($delta) {
		$this->start += $delta;
	}

	public function addCheck(Check $c) {
		$this->checks[] = array('subid' => $this->subId, 'check' => $c);
		$c->setLocation(new SubErrorMsg($this, $this->subId));
		$this->subId++;
	}

	public function setupHtml(&$html) {
		$file = 'errorMessage.html';
		if ($this->tplVariation) {
			$fileParts = pathinfo($file);
			$fileName = $fileParts['filename'].'.'.$this->tplVariation.'.'.$fileParts['extension'];
		} else
			$fileName = $file;

		$file = dirname(__FILE__).'/../../templates/'.$fileName;
		if (!file_exists($file))
			$file = dirname(__FILE__).'/../templates/'.$fileName;
		$tpl = new ElemTemplate($file);

		$lines = '';
		foreach ($this->checks as $c) {
			$lines .= $tpl->apply(array(
				'id'      => $this->uniqueName.'_'.$c['subid'],
				'display' => $c['check']->failed ? 'true' : 'false',
				'msg'     => $c['check']->getErrorMsg()
			));
		}
		$html = substr_replace($html, $lines, $this->start, $this->length);
	}

	public function __get($name) {
		if ($name == 'start' || $name == 'length' || $name == 'name' || $name == 'uniqueName')
			return $this->$name;
		else
			throw new Exception('Unknown property: '.get_class($this).'::$'.$name);
	}
}

class SubErrorMsg {
	protected $owner;
	protected $subId;

	public function __construct(ErrorMsg $owner, $subId) {
		$this->owner = $owner;
		$this->subId = $subId;
	}

	public function __get($name) {
		if ($name == 'uniqueName')
			return $this->owner->uniqueName.'_'.$this->subId;
	}
}

?>