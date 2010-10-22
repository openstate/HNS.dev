<?php
require_once 'routes/RouteInterface.interface.php';

class MenuRoute implements RouteInterface {
	
	protected $menu = array();
	protected $references = array();
	protected $rows = array();
	
	public function __construct($rows = array()) {
		$this->rows = $rows;
	}
	
	public function setRows($rows) {
		$this->rows = $rows;
		$this->menu = array();
		$this->references = array();		
	}
	
	public function match($path) {
		$this->buildTree(); //make sure we have the menu structure loaded
		$parameters = explode('/', trim($path, '/'));
		if (($result = $this->walkTree($parameters, $this->menu)) !== false) {				
			return explode('/', trim($result, '/'));
		}
		return false; 	
	}
	
	public function assemble($data = array()) {
		$this->buildTree(); //make sure we have the menu structure loaded
		
		settype($data, 'array');		
		if (!isset($data[0]) || empty($data[0]) ) {
			return '/' . implode('/', $data);
		}
		
		//use reverse menu routing
		$path = implode('/', $data);
		$foundRoute = false;
		$minPenalty = strlen($path);
		foreach ($this->references as $route) {			
			if ($route['rewrite'] === '' || strpos($path, $route['rewrite']) === 0) {
				$result = $this->getParentPath($route['parent_id']) . '/' . $route['path'];						
				//Penalty is the difference between the rewrite and the path we asked for
				$penalty = strlen($path)-strlen($route['rewrite']);
				if ($penalty < $minPenalty) {
					$foundRoute = $result . substr($path, strlen($route['rewrite']));
					$minPenalty = $penalty;
				}				
			}
		}		
		return $foundRoute;
	}
	
	
	protected function walkTree($path, $menu) {		
		if (count($path) && !empty($path[0])) {			
			foreach($menu as $m) {				
				if ($m['path'] == $path[0]) {
					if (isset($m['children'])) {
						$result = $this->walkTree(array_slice($path, 1), $m['children']);
						if ($result !== false) {							
							return $result;
						}
					}
					array_shift($path);		
					return $m['rewrite'] . '/' . implode('/', $path);
				}
			}
		}
		return false;
	}
	
	protected function getParentPath($id) {
		if (isset($this->references[$id])) {
			return $this->getParentPath($this->references[$id]['parent_id']) . '/' . $this->references[$id]['path'];
		}
		return '';
	}
	
	protected function buildTree() {
		
		
		if (empty($this->menu)) {	
						
			foreach($this->rows as $row) {
				$thisref = &$this->references[ $row['id'] ];
				//We need this, or we'll overwrite the reference.
				foreach($row as $key => $value) 
					$thisref[$key] = $row[$key];				
	
				if ($row['parent_id'] === null) {
					$this->menu[ $row['id'] ] = &$thisref;
				} else {
					$this->references[ $row['parent_id'] ]['children'][ $row['id'] ] = &$thisref;
				}
			}
		}	
	}
}