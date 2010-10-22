<?php

interface RouteInterface {
	
	public function match($path);
	public function assemble(Destination $destination);
}

?>