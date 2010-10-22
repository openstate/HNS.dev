<?php

require_once('ErrorMsg.class.php');

class ElemTemplate {
	protected $template;
	protected $vars;

	private $varRegex   = '/\{\$([^:}]+)(:[^}]*)?\}/';
	private $errorRegex = '/\{#error[^}]*\}/';

	public function __construct($file) {
		$this->template = file_get_contents($file);
	}

	protected function accEscape($match) {
		if ($match[0] == '{')
			return '{ldelim}';
		else
			return '{rdelim}';
	}

	protected function applyVar($match) {
		if (isset($this->vars[$match[1]])) {
			$var = $this->vars[$match[1]];

			if (isset($match[2])) {
				$params = explode(':', substr($match[2], 1));
				foreach ($params as $par) {
					if ($par == 'smarty') {
						// Escape { and } for use with Smarty
						$var = preg_replace_callback('/[{}]/', array($this, 'accEscape'), $var);
					}
				}
			}

			return $var;
		} else
			throw new ParseException('Unknown variable \''.$match[1].'\' in template for element \''.get_class($this).'\'');
	}

	public function apply($vars) {
		$this->vars = $vars;
		return preg_replace_callback($this->varRegex, array($this, 'applyVar'), $this->template);
	}

	public function findVarPositions($charOffset) {
		preg_match_all($this->varRegex, $this->template, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		$result = array();
		foreach ($matches as $m)
			$result[] = array('name' => $m[1][0], 'start' => $m[0][1] + $charOffset, 'length' => strlen($m[0][0]));

		return $result;
	}

	public function findErrorPositions($charOffset) {
		preg_match_all($this->errorRegex, $this->template, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		$errors = array();
		foreach ($matches as $m) {
			$e = new ErrorMsg($m[0][1] + $charOffset, $m[0][0]);
			$errors[$e->name] = $e;
		}

		preg_match_all($this->varRegex, $this->template, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

		$positions = array();
		if (count($matches) > 0 && count($errors) > 0) {
			// Do we pick the error position preceding or following a var?
			$currErr = reset($errors);
			$nextErr = next($errors);
			$useNext = $currErr->start - $charOffset > $m[0][0][1];

			// Find all possible error positions for each var
			foreach ($matches as $m) {
				// Skip to closest preceding error position.
				while ($nextErr && $nextErr->start - $charOffset < $m[0][1]) {
					$currErr = $nextErr;
					$nextErr = next($errors);
				}

				if (isset($m[2])) {
					$params = explode(':', substr($m[2][0], 1));
					foreach ($params as $par) {
						if (substr($par, 0, 6) == 'error=') {
							$name = substr($par, 6);
							if ($name == '#next')
								$useNext = true;
							else if ($name == '#prev')
								$useNext = false;
							else {
								if (!isset($errors[$name]))
									throw new ParseException('Unknown error name: '.$name);
								$positions[$m[1][0]] = $errors[$name];
							}
						} else if ($par == 'smarty') {
							// Nothing
						}
					}
				}
				if (!isset($m['err'])) {
					if ($useNext && $nextErr)
						$positions[$m[1][0]] = $nextErr;
					else
						$positions[$m[1][0]] = $currErr;
				}
			}
		}

		return array('locations' => $errors, 'links' => $positions);
	}
}

?>