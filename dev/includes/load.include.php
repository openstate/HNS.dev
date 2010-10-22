<?php

	function processor_filter($line) {
		return trim(reset(explode(':', $line))) == 'processor';
	}
	
	function load() {
		if ($cpuinfo = @file_get_contents('/proc/cpuinfo'))
			$numcores = count(array_filter(explode("\n", $cpuinfo), 'processor_filter'));
		else {
			trigger_error('Can\'t read /proc/cpuinfo, assuming 1 core', E_USER_WARNING);
			$numcores = 1;
		}
		if ($loadavg = @file_get_contents('/proc/loadavg'))
			$load = (float) reset(explode(' ', $loadavg));
		else {
			trigger_error('Can\'t read /proc/loadavg, assuming 0 load', E_USER_WARNING);
			$load = 0;
		}
		return $load / $numcores;
	}

?>