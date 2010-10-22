<?php

/*
	Class: HTMLDateInput
	Select a date from three dropdowns.
*/
class HTMLDateInput extends HTMLInput {
	protected $calendar = false;
	protected $toggleHTML = '<img style="vertical-align:middle" src="/images/calendar.gif" alt="+" />';
	protected $smartyAttribs = array(
		'startyear'    => '+0',
		'endyear'      => '+4',
		'reverseYears' => false,
		'fields'       => 'DMY',
		'field_separator' => null,
		'year_empty' => null,
		'month_empty' => null,
		'day_empty' => null
	);

	public function addAttributes(/*DOMNamedNodeMap*/ $attribs) {
		parent::addAttributes($attribs);

		foreach ($this->attributes as $name => $val) {
			if (array_key_exists($name, $this->smartyAttribs)) {
				$this->smartyAttribs[$name] = $val;
				unset($this->attributes[$name]);
			}
		}
	}

	public function setOnChange($js) {
		$this->addAttributes(array('onclick' => (isset($this->attributes['onclick']) ? $this->attributes['onclick'].';' : '').$js));
	}
	public function clearOnChange() {
		unset($this->attributes['onclick']);
	}

	public function useParser(DescParser $parser) {
		if ($parser->hasTemplate('dateinput')) {
			$this->toggleHTML = '';
			foreach ($parser->applyTemplate('dateinput', array(), false) as $html)
				$this->toggleHTML.= $html->getHTML();
		}
	}

	public function getHTML() {
		$attr = '';
		$hasOnchange = false;
		$hasAllFields = strpos($this->smartyAttribs['fields'], 'D')!==false && strpos($this->smartyAttribs['fields'], 'M')!==false && strpos($this->smartyAttribs['fields'], 'Y')!==false;
		if (isset($this->attributes['calendar']))
			$hasAllFields = !(bool) $this->attributes['calendar'];

		foreach ($this->attributes as $name => $val) {
			if ($name == 'onchange' && $hasAllFields) {
				$attr.= ' '.$name.'="'.$val.';dateSelected('.$this->name.'_cal)"';
				$hasOnchange = true;
			} else if (!in_array($name, array('id', 'calendar')))
				$attr.= ' '.$name.'="'.$val.'"';
		}
		if (!$hasOnchange && $hasAllFields)
			$attr.= ' onchange="dateSelected('.$this->name.'_cal)"';
		/*
		$minYear = preg_match('/^[+-]\d+$/', $this->smartyAttribs['startyear']) ? date('Y') + $this->smartyAttribs['startyear'] : $this->smartyAttribs['startyear'];
		$maxYear = preg_match('/^[+-]\d+$/', $this->smartyAttribs['endyear'])   ? date('Y') + $this->smartyAttribs['endyear']   : $this->smartyAttribs['endyear'];
		*/

		if (strpos($this->smartyAttribs['fields'], 'D')!==false)
			$dayPart = ' day_extra=\'id="'.$this->attributes['id'].'_Day" class="date_day"\' day_format=\'%d\' day_value_format=\'%02d\'';
		else
			$dayPart = ' display_days=false';

		if (strpos($this->smartyAttribs['fields'], 'M')!==false)
			$monthPart = ' month_extra=\'id="'.$this->attributes['id'].'_Month" class="date_month"\'';
		else
			$monthPart = ' display_months=false';
		
		$prefix = '';
		if (strpos($this->smartyAttribs['fields'], 'Y')!==false)
			if ($this->smartyAttribs['startyear'] == 'current') {
				$prefix = '{assign var=timestamp value=$'.$this->formDataVar.'.'.$this->name.'|strtotime}{if $timestamp eq false or $timestamp eq \'-1\'}{assign var=year value=\'+0\'}{else}{assign var=year value=\'Y\'|date:$timestamp}{/if}';
				$yearPart  = ' year_extra=\'id="'. $this->attributes['id'].'_Year" class="date_year"\' start_year=$year|default:\'0\' end_year=\''.$this->smartyAttribs['endyear'].'\''.($this->smartyAttribs['reverseYears'] ? ' reverse_years=true' : '');
			} else {
				$yearPart  = ' year_extra=\'id="'. $this->attributes['id'].'_Year" class="date_year"\' start_year=\''.$this->smartyAttribs['startyear'].'\' end_year=\''.$this->smartyAttribs['endyear'].'\''.($this->smartyAttribs['reverseYears'] ? ' reverse_years=true' : '');
			}
		else
			$yearPart = ' display_years=false';
		
		$defaultEmpty = false;
		if(isset($this->smartyAttribs['day_empty'])) {
			$dayPart .= ' day_empty="'.$this->smartyAttribs['day_empty'].'"';
			$defaultEmpty = true;
		}
		if(isset($this->smartyAttribs['month_empty'])) {
			$monthPart .= ' month_empty="'.$this->smartyAttribs['month_empty'].'"';
			$defaultEmpty = true;
		}
		if(isset($this->smartyAttribs['year_empty'])) {
			$yearPart .= ' year_empty="'.$this->smartyAttribs['year_empty'].'"';
			$defaultEmpty = true;
		}
			
		$result = $prefix .
		  '{html_select_date prefix=\'\' field_array=\''.$this->name.'\' time=$'.$this->formDataVar.'.'.$this->name.
		  ($defaultEmpty ? '|default:\'--\'' : '').' field_order=\''.$this->smartyAttribs['fields'].'\' '.$dayPart.$monthPart.$yearPart.
			(isset($this->smartyAttribs['field_separator']) ? 'field_separator=\''.$this->smartyAttribs['field_separator'].'\'' : '').
		  ($attr!='' ? ' all_extra=\''.$attr.'\'' : '').'}';
		if ($hasAllFields)
			// Add JS calendar if all fields are present.
		  $result.= ' <a href="javascript:;" onclick="javascript:openCalendar('.$this->name.'_cal)">'.$this->toggleHTML.'</a>'.
			'<div id="'.$this->name.'_div" style="display:none;height:0px">
				<iframe src="javascript:\'<html></html>\';"scrolling="no" frameborder="0" style="position:absolute;width:200px;height:200px;display:block;filter:progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=0)"></iframe>
				<div id="'.$this->name.'_div_in" style="position:absolute;" class="calendar_div_in"><script type="text/javascript">'.
				$this->name.'_cal = initCalendar(\''.$this->name.'_div_in\', \''.$this->name.'_div\', \''.$this->name.'_cal\', document.getElementById(\''.$this->attributes['id'].'_Day\').form, \''.$this->name.'\');'.
				$this->name.'_cal.minYear='.(preg_match('/^[+-]\d+$/', $this->smartyAttribs['startyear']) ? '(new Date()).getFullYear()' : '').$this->smartyAttribs['startyear'].';'.
				$this->name.'_cal.maxYear='.(preg_match('/^[+-]\d+$/', $this->smartyAttribs['endyear'])   ? '(new Date()).getFullYear()' : '').$this->smartyAttribs['endyear'].
				'</script></div></div>';

		return $result;
	}

	public function getExtraJS($js = '') {
		$js = parent::getExtraJS($js);
		if (isset($this->attributes['calendar']) && $this->attributes['calendar']) {
			$js .= '		window.addEvent(\'domready\', function() {
				new Calendar({\''.$this->attributes['id'].'_Year\': { \''.$this->attributes['id'].'_Day\': \'d\', \''.$this->attributes['id'].'_Month\': \'m\', \''.$this->attributes['id'].'_Year\': \'Y\' }}, {
					navigation: 2
				});
			});
			';
		}
		return $js;
	}

	protected function getRealCondition($addRequired) {
		return $this->condition;
	}

	public function getConversions() {
		$catExpr = array();
		if (strpos($this->smartyAttribs['fields'], 'Y')!==false) {
			$catExpr[]= new FormPostExpr($this->name, '\'Year\'');
			$catExpr[]= new ValueExpr('-');
		} else {
			$catExpr[]= new ValueExpr('0000-');
		}

		if (strpos($this->smartyAttribs['fields'], 'M')!==false) {
			$catExpr[]= new FormPostExpr($this->name, '\'Month\'');
			$catExpr[]= new ValueExpr('-');
		} else {
			$catExpr[]= new ValueExpr('01-');
		}

		if (strpos($this->smartyAttribs['fields'], 'D')!==false) {
			$catExpr[]= new FormPostExpr($this->name, '\'Day\'');
		} else {
			$catExpr[]= new ValueExpr('01');
		}

		return array(new AssignStatement(
			new FormElExpr($this),
			new TernaryExpr(
				new IssetExpr(new FormPostExpr($this->name)),
				new NaryExpr('.', $catExpr),
				new ValueExpr(null)
			)
		));
	}
}

class HTMLDateTimeInput extends HTMLDateInput {
	protected $minuteInterval = 1, $secondInterval = 1, $showSeconds = false;

	public function addAttributes(/*DOMNamedNodeMap*/ $attribs) {
		parent::addAttributes($attribs);

		foreach (array('minute_interval' => 'minuteInterval', 'second_interval' => 'secondInterval', 'display_seconds' => 'showSeconds') as $attr => $var) {
			if (isset($this->attributes[$attr])) {
				$this->$var = $this->attributes[$attr];
				unset($this->attributes[$attr]);

				if ($attr == 'secondinterval')
					$this->showSeconds = true;
			}
		}
	}

	public function getHTML() {
		$attr = '';
		foreach ($this->attributes as $name => $val) {
			if (!in_array($name, array('id', 'calendar')))
				$attr.= ' '.$name.'="'.$val.'"';
		}
		return
			parent::getHTML().
		  ' {html_select_time prefix=\'\' field_array=\''.$this->name.'\''.
		  ' time=$'.$this->formDataVar.'.'.$this->name.' minute_interval=\''.$this->minuteInterval.'\''.
			' display_seconds='.($this->showSeconds ? 'true' : 'false').
			($this->showSeconds ? ' second_interval=\''.$this->secondInterval.'\'' : '').
			' hour_extra=\'id="'.  $this->attributes['id'].'_Hour" class="date_hour"\''.
			' minute_extra=\'id="'.$this->attributes['id'].'_Minute" class="date_minute"\''.
			' second_extra=\'id="'. $this->attributes['id'].'_Second" class="date_second"\''.
		  ($attr!='' ? ' all_extra=\''.$attr.'\'' : '').'}';
	}

	public function getConversions() {
		$nary = new NaryExpr('.', array(
			new FormPostExpr($this->name, '\'Year\''), new ValueExpr('-'),
			new FormPostExpr($this->name, '\'Month\''), new ValueExpr('-'),
			new FormPostExpr($this->name, '\'Day\''), new ValueExpr(' '),
			new FormPostExpr($this->name, '\'Hour\''), new ValueExpr(':'),
			new FormPostExpr($this->name, '\'Minute\'')
		));
		if ($this->showSeconds)	{
			$nary->add(new ValueExpr(':'));
			$nary->add(new FormPostExpr($this->name, '\'Second\''));
		}
		return array(new AssignStatement(new FormElExpr($this), $nary));
	}
}

class HTMLTimeInput extends HTMLDateTimeInput {
	public function getHTML() {
		$attr = '';
		foreach ($this->attributes as $name => $val) {
			if ($name != 'id')
				$attr.= ' '.$name.'="'.$val.'"';
		}
		return
		  '{html_select_time prefix=\'\' field_array=\''.$this->name.'\''.
		  ' time=$'.$this->formDataVar.'.'.$this->name.' minute_interval=\''.$this->minuteInterval.'\''.
			' display_seconds='.($this->showSeconds ? 'true' : 'false').
			($this->showSeconds ? ' second_interval=\''.$this->secondInterval.'\'' : '').
			' hour_extra=\'id="'.  $this->attributes['id'].'_Hour" class="date_hour"\''.
			' minute_extra=\'id="'.$this->attributes['id'].'_Minute" class="date_minute"\''.
			' second_extra=\'id="'. $this->attributes['id'].'_Second" class="date_second"\''.
		  ($attr!='' ? ' all_extra=\''.$attr.'\'' : '').'}';
	}

	public function getConversions() {
		$nary = new NaryExpr('.', array(
			new FormPostExpr($this->name, '\'Hour\''), new ValueExpr(':'),
			new FormPostExpr($this->name, '\'Minute\'')
		));
		if ($this->showSeconds)	{
			$nary->add(new ValueExpr(':'));
			$nary->add(new FormPostExpr($this->name, '\'Second\''));
		}
		return array(new AssignStatement(new FormElExpr($this), $nary));
	}
}

HTMLInputFactory::register('date',     'HTMLDateInput');
HTMLInputFactory::register('datetime', 'HTMLDateTimeInput');
HTMLInputFactory::register('time',     'HTMLTimeInput');

?>