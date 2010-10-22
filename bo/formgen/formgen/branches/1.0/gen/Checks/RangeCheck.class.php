<?php

require_once('Check.class.php');

class RangeCheck extends Check {
	protected $validOptions = array('min' => true, 'max' => true);

	protected $formEl;

	protected $min  = false;
	protected $max  = false;

	public function getCode($elem) {
		$result = '';
		if ($this->min !== false) {
			$result = $elem.'>=';
			if (is_numeric($this->min))
				$result.= $this->min;
			else
				$result.= '\''.addslashes($this->min).'\'';
		}

		if ($this->max !== false) {
			if ($result)
				$result.= ' && ';
			$result.= $elem.'<=';
			if (is_numeric($this->max))
				$result.= $this->max;
			else
				$result.= '\''.addslashes($this->max).'\'';
		}

		return '('.$result.')';
	}

	public function getPHP($indent) {
		return $this->getCode($this->formEl->getPHP($indent));
	}

	public function getJS($indent, $parser) {
		return $this->getCode($this->formEl->getJS($indent, $parser));

	}

	public function getHTML() {
		return 'true';
	}

	public function getExpr(FormElExpr $formEl) {
		if ($this->min === false && $this->max === false)
			throw new Exception('Must give at least a minimum or maximum for a range check');
		$this->formEl = $formEl;
		return new GenericExpr(array($this, 'getPHP'), array($this, 'getJS'), array($this, 'getHTML'));
	}

	protected $errorMsgName = 'range';
}

CheckFactory::register('range', 'RangeCheck');

?>