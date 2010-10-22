<?php

require_once('record/Record.abstract.php');

class Project extends Record {
	protected $tableName = 'prj_projects';

	protected $config = array(
		'id' => array(),
		'name' => array(),
		'logo' => array(),
		'screenshot' => array(),
		'date' => array(),
		'website' => array(),
		'description' => array(),
		'rss' => array(),
		'license' => array(),
		'rights_read' => array(),
		'rights_write' => array(),
		'key' => array(),
		'published' => array(),
		'user_id' => array(),
	);
	
	protected $hasOneConfig = array(
		'user' => array(
			'class' => 'User',
			'local' => 'user_id',
			'foreign' => 'user_id',
		),
	);

	protected $hasManyConfig = array(
		'files' => array(
			'class' => 'ProjectFile',
			'local' => 'id',
			'foreign' => 'project_id',
		),
	);

	public function init() {
		$this->registerPlugin('Objectable', array(
			'logo' => array(
				'type' => 'Image',
				'args' => array(
					'path' => '/assets/files/images/',
					'default' => '_logo.gif',
					'throw_exception' => true,
				),
			),
			'screenshot' => array(
				'type' => 'Image',
				'args' => array(
					'path' => '/assets/files/images/',
					'default' => '_screenshot.gif',
					'throw_exception' => true,
				),
			),
		));
	}
	
	public function getWikiCode() {
		$domain = 'http'.(@$_SERVER['HTTPS'] ? 's' : '').'://'.$_SERVER['HTTP_HOST'];
	
		return <<<EOF
'''Name''': {$this->name}
'''Data''': {$this->date}
'''Website''': {$this->website}

<span class="plainlinks">[{$domain}/modules/projects/index/change/{$this->id} Change project]</span>
EOF;
	}
}

?>