<?php

class datetimeElement extends InputElement {
	protected $name = '';
	protected $value = array(
		'Day'  => null, 'Month'  => null, 'Year' => null,
		'Hour' => null, 'Minute' => null, 'Second' => null
	);

	protected $opts = array(
		'start_year'      => '+0',
		'end_year'        => '+4',
		'reverse_years'   => 'false',
		'fields'          => 'DMY',
		'year_empty'      => null,
		'month_empty'     => null,
		'day_empty'       => null,

		'minute_interval' => 1,
		'second_interval' => 1,
	);
	protected $childValues;

	public function __construct() {
		parent::__construct();

		$this->childValues = array(
			'day'    => new SimpleInputValue($this, 'day',    $this->value['Day'],    array(array($this, 'getJSSubValue'), 'Day')),
			'month'  => new SimpleInputValue($this, 'month',  $this->value['Month'],  array(array($this, 'getJSSubValue'), 'Month')),
			'year'   => new SimpleInputValue($this, 'year',   $this->value['Year'],   array(array($this, 'getJSSubValue'), 'Year')),
			'hour'   => new SimpleInputValue($this, 'hour',   $this->value['Hour'],   array(array($this, 'getJSSubValue'), 'Hour')),
			'minute' => new SimpleInputValue($this, 'minute', $this->value['Minute'], array(array($this, 'getJSSubValue'), 'Minute')),
			'second' => new SimpleInputValue($this, 'second', $this->value['Second'], array(array($this, 'getJSSubValue'), 'Second'))
		);
	}

	public function getHtml(HtmlContext $context) {
		parent::getHtml($context);
		$bool = array(false => 'false', true => 'true');
		$result = $this->applyTemplate('datetime.html',
			array_merge(
				$this->opts,
				array(
					'name'  => $this->state['name'],
					'value' => sprintf(
						'%d-%d-%d %d:%d:%d',
						$this->value['Year'], $this->value['Month'], $this->value['Day'],
						$this->value['Hour'], $this->value['Minute'], $this->value['Second']
					),
					'display_days'    => $bool[strpos($this->opts['fields'], 'D') !== false],
					'display_months'  => $bool[strpos($this->opts['fields'], 'M') !== false],
					'display_years'   => $bool[strpos($this->opts['fields'], 'Y') !== false],
					'display_hours'   => $bool[strpos($this->opts['fields'], 'h') !== false],
					'display_minutes' => $bool[strpos($this->opts['fields'], 'm') !== false],
					'display_seconds' => $bool[strpos($this->opts['fields'], 's') !== false],

					'year_empty'      => $this->opts['year_empty']  === null ? 'null' : '\''.$this->opts['year_empty'].'\'',
					'month_empty'     => $this->opts['month_empty'] === null ? 'null' : '\''.$this->opts['month_empty'].'\'',
					'day_empty'       => $this->opts['day_empty']   === null ? 'null' : '\''.$this->opts['day_empty'].'\'',

					'sm_year_empty'      => $this->opts['year_empty']  === null ? '' : 'year_empty=\''. $this->opts['year_empty'].'\'',
					'sm_month_empty'     => $this->opts['month_empty'] === null ? '' : 'month_empty=\''.$this->opts['month_empty'].'\'',
					'sm_day_empty'       => $this->opts['day_empty']   === null ? '' : 'day_empty=\''.  $this->opts['day_empty'].'\'',
				)
			),
			$context);
		return $result;
	}

	public function parse(DOMElement $node, $parser, ParseContext $context) {
		parent::parse($node, $parser, $context);

		foreach ($this->opts as $key => &$value) {
			if ($node->hasAttribute($key))
				$value = $node->getAttribute($key);
		}

		$this->source = $node->getAttribute('values');
	}

	public function getEvent($name) {
		return array(
			'add'  => 'function(eventname, handler) { bindDateEvent(this, \''.addslashes($name).'\', eventname, handler) }.bind(form)',
			'name' => 'change'
		);
	}

	public function getConditions() {
		$result = parent::getConditions();
		$result[] = array(
			'extraJS' => 'initCalendar(form, \''.addslashes($this->state['name']).'\');'
		);

		return $result;
	}

	public function getAllValues() {
		$result = parent::getAllValues();
		$result[$this->name]['full'] = $this->getValue();
		return $result;
	}

	public function getChildValues() {
		$children = array();
		foreach (array(
			'D' => 'day',
			'M' => 'month',
			'Y' => 'year',
			'h' => 'hour',
			'm' => 'minute',
			's' => 'second'
		) as $field => $name) {
			if (strpos($this->opts['fields'], $field) !== false)
				$children[$name] = $this->childValues[$name];
		}
		return $children;
	}

	public function getValue() {
		$date = sprintf('%04d-%02d-%02d', $this->value['Year'], $this->value['Month'], $this->value['Day']);
		$time = sprintf('%02d:%02d:%02d', $this->value['Hour'], $this->value['Minute'], $this->value['Second']);
		$showDate = preg_match('/[DMY]/', $this->opts['fields']);
		$showTime = preg_match('/[hms]/', $this->opts['fields']);
		if ($showDate && $showTime)
			return $date.' '.$time;
		else if ($showDate)
			return $date;
		else
			return $time;
	}

	public function setFromData($data, $raw = false) {
		if (isset($data[$this->name])) {
			if (!is_array($data[$this->name]) && $raw) {
				$dateMatch = '(?P<year>\d{2,4})?-(?P<month>\d{1,2})?-(?P<day>\d{1,2})?';
				$timeMatch = '(?P<hour>\d{1,2})?:((?P<minute>\d{1,2})?(:((?P<second>\d{1,2})(\.[0-9]+))?)?)?';
				if (!preg_match('/^'.$dateMatch.'$/', $data[$this->name], $matches) &&
				    !preg_match('/^'.$timeMatch.'$/', $data[$this->name], $matches) &&
				    !preg_match('/^'.$dateMatch.' '.$timeMatch.'$/', $data[$this->name], $matches))
				  return;
				$data = $matches;
			} else
				$data = $data[$this->name];

			// Element-wise initing
			foreach ($data as $key => $value)
				$this->value[ucfirst($key)] = $value;
		}
	}

	public function isGiven() {
		return (bool)$this->value;
	}

	public function getJSValue() {
		return 'getDateValue(form, \''.addslashes($this->state['name']).'\')';
	}

	public function getJSSubValue($subElem) {
		return 'form[\''.addslashes($this->state['name'].'['.ucfirst($subElem->getName()).']').'\'].value';
	}
}

?>