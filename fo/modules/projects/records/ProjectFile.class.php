<?php

require_once('record/Record.abstract.php');

class ProjectFile extends Record {
	protected $tableName = 'prj_files';

	protected $config = array(
		'id' => array(),
		'project_id' => array(),
		'file' => array(),
		'filename' => array(),
		'version' => array(),
		'description' => array(),
		'language' => array(),
		'published' => array(),
	);

	protected $hasOneConfig = array(
		'project' => array(
			'class' => 'Project',
			'local' => 'project_id',
			'foreign' => 'id',
		),
	);

	public function init() {
		$this->registerPlugin('Objectable', array(
			'file' => array(
				'type' => 'File',
				'args' => array(
					'path' => '/assets/files/source/',
					'reverseTypes' => true,
					'throw_exception' => true,
				),
			),
		));
	}
}

?>