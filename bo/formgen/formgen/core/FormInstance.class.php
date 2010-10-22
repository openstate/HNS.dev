<?php

require_once('FormParser.class.php');

class FormInstance {
	protected $form;
	protected $callbacks = array();

	protected $unmatchedChecks = array();

	public function __construct($source, $parseAsString = false) {
		$parser = new FormParser();
		if ($parseAsString)
			$parser->loadString($source);
		else
			$parser->loadFile($source);
		$this->form = $parser->parse();
	}

	public function addCallback($name, $callback) {
		$this->callbacks[$name] = $callback;
	}

	public function isValid() {
		return $this->form->isValid($this->callbacks);
	}

	public function setRawData($data) {
		foreach ($this->form->getInputs() as $input) {
			$input->setFromData($data, true);
		}
	}

	public function setPostData($post, $files = array()) {
		$data = array_merge($post, $files);
		foreach ($this->form->getInputs() as $input) {
			$input->setFromData($data, false);
		}
	}

	public function getValues() {
		$result = array();

		foreach ($this->form->getInputs() as $input) {
			$result = array_merge($result, $input->getAllValues());
		}

		return $result;
	}

	public function getUnmatchedChecks() {
		return $this->unmatchedChecks;
	}

	public function html() {
		$ctx = new HtmlContext();
		$ctx->namedLocs = $this->form->getNamedErrorLocations();
		$result = $this->form->getHtml($ctx);

		return $result;
	}

	protected function inputSort($a, $b) {
		if ($a['input'] === $b['input'])
			return 0;
		else
			return $b['idx'] - $a['idx'];
	}

	public function js() {
		$result = $this->form->getConditions();

		$extra = '';

		$checks = $conditions = array();
		// Divide into checks & conditions
		foreach ($result as $item) {
			if (isset($item['extraJS']))
				$extra .= $item['extraJS']."\n";
			else if (count($item['checkTargets']) > 0)
				$checks[] = $item;
			else
				$conditions[] = $item;
		}

		// Group by inputs
		$inputList = array();
		foreach ($conditions as $c) {
			foreach ($c['targets'] as $t) {
				if (!isset($inputList[$t['name']])) {
					$event = $t['target']->getEvent($t['name']);
					$inputList[$t['name']] = array('addEvent' => $event['add'], 'eventName' => $event['name']);
				}
				$inputList[$t['name']]['conditions'][] = array('condition' => $c['condition'], 'elem' => $c['id'], 'method' => $c['method']);
			}
		}

		$checksByInput = array();
		$cnt = 0;
		foreach ($checks as $c) {
			foreach ($c['checkTargets'] as $t) {
				$checksByInput[$t['name']]['checks'][] = $c;
				if (!isset($checksByInput[$t['name']]['idx']))
					$checksByInput[$t['name']]['idx'] = $cnt++;

				$n = $checksByInput[$t['name']]['idx'];
				$inputList[$t['name']]['checks'][$n] = $c;
				$event = $t['target']->getEvent($t['name']);
				if (count($event) > 0) {
					$inputList[$t['name']]['addEvent'] = $event['add'];
					$inputList[$t['name']]['eventName'] = $event['name'];
				}
			}
			foreach ($c['targets'] as $t) {
				if (isset($checksByInput[$t['name']]['idx'])) {
					$n = $checksByInput[$t['name']]['idx'];
					$inputList[$t['name']]['checks'][$n] = $c;

					$event = $t['target']->getEvent($t['name']);
					if (count($event) > 0) {
						$inputList[$t['name']]['addEvent'] = $event['add'];
						$inputList[$t['name']]['eventName'] = $event['name'];
					}
				}
			}
		}

		$result = $extra.'var checks = [';

		foreach ($checksByInput as &$i) {
			if ($i['idx'] > 0)
				$result .= ",\n";
			$result .= 'function(form) {'."\n";
			$result .= "\tif (!this.autoValidate) return true;\n";
			$result .= "\tvar result = false;\n";

			foreach ($i['checks'] as $c)
				$result .= "\t".'this.setVisibility($(\''.addslashes($c['id']).'\'), false);'."\n";
			$result .= "\n";

			$first = true;
			foreach ($i['checks'] as $c) {
				if ($first)
					$result .= "\tif ";
				else
					$result .= "\telse if ";

				$result .= '('.$c['condition'].') this.setVisibility($(\''.addslashes($c['id']).'\'), true);'."\n";
				$first = false;
			}
			$result .= "\telse result = true;\n\treturn result;\n}";
		}
		unset($i);

		$result .= "];\n\n";


		$funcs = 0;
		$result .= 'var events = [';
		foreach ($inputList as $name => $i) {
			if ($funcs > 0)
				$result .= ",\n";
			if (isset($i['addEvent'])) {
				$fn = '{ binder: '.$i['addEvent'].', event: \''.addslashes($i['eventName']).'\', handler: function(event, form) {'."\n";
				if (isset($i['conditions'])) {
					foreach ($i['conditions'] as $c)
						$fn .= "\t".'this.'.$c['method'].'($(\''.addslashes($c['elem']).'\'), '.$c['condition'].');'."\n";
				}

				if (isset($i['checks']))
					foreach ($i['checks'] as $c)
						foreach ($c['checkTargets'] as $t)
							$fn .= "\t".'this.checks['.$checksByInput[$t['name']]['idx'].'](form);'."\n";
				$fn .= '} }';
				$result .= $fn;

				$funcs++;
			}
		}

		$result .= "];\n\n";

		return $result;
	}
}

?>