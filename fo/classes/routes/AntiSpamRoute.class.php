<?php

require_once 'routes/RouteInterface.interface.php';
require_once 'Destination.class.php';

/**
 * Antispam route class
 * routes /images/filler.gif to the anti spam action
 *
 * @package default
 * @author Harro
 **/
class AntispamRoute implements RouteInterface {	
	public function match($path) {
		$destination = new Destination();
		$path = rtrim($path, '/');
		if ($path == '/images/filler.gif') {
			$destination->fromUrlString('/default/index/antispam');
			return $destination;
		}
		return false;		
	}
	
	public function assemble(Destination $destination) {
		if ($destination->toUrlString() == 'default/index/antispam') {
			return '/images/filler.gif';
		}
		return false;		
	}
}

?>