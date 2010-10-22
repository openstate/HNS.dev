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
	
	public function getWikiTitle() {
		return 'Project:'.$this->name;
	}
	
	public function isValidTitle($title) {
		if ($title == $this->name) return true;
		
		if (preg_match('![]#<>|{}/?+[]!', $title)) return false;
		if ($this->db->query('SELECT 1 FROM %t WHERE name = % AND id != %', $this->tableName, $title, $this->id)->fetchCell())
			return false;
			
		require_once('Wiki.class.php');
		if (Wiki::inst()->exists('Project:'.$title)) return false;
	}
	
	public function getWikiContent() {
		$strings = array(
			'owner', 'talk', 'contribs', 'date', 'website', 'rss', 'license', 'description', 'files', 'change', 'date_format'
		);

		$tr = new GettextPO(dirname(__FILE__).'/../locales/en/projects.po');
		foreach ($strings as $s)
			$$s = $tr->getMsgstr('project.'.$s);
			
		$user = $this->user->user_name;
		$logo = $this->logo->getThumbnail(200);
		$screenshot = $this->screenshot->getThumbnail(200);
		$date_str = strftime($date_format, strtotime($this->date));
		
		$change = strtolower($change);
		
		$server = 'http'.(@$_SERVER['HTTPS'] ? 's' : '').'://'.$_SERVER['HTTP_HOST'];
		
		$wiki = '';
		if ($logo || $screenshot) {
			$wiki .= "<div style=\"float:right;\">\n";
			if ($logo) $wiki .= "<div style=\"margin: 5px; padding: 5px; border: 1px solid black;\">{$server}$logo</div>\n\n";
			if ($screenshot) $wiki .= "<div style=\"margin: 5px; padding: 5px; border: 1px solid black;\">{$server}$screenshot</div>\n\n";
			$wiki .= "</div>\n\n";
		}
		$wiki .= "<guard user=\"".htmlspecialchars($user)."\"><div style=\"float: right;\">&#91;[[Redirect:/modules/projects/index/change/{$this->id}/|$change]]]</div>\n\n</guard>";
		$wiki .= "* '''$owner''': [[User:$user|$user]] ([[User talk:$user|$talk]] • [[Special:Contributions/$user|$contribs]])''\n";
		$wiki .= "* '''$date''': {$date_str}\n";
		$wiki .= "* '''$website''': {$this->website}\n";
		if ($this->rss)	$wiki .= "* '''$rss''': {$this->rss}\n";
		$wiki .= "* '''$license''': {$this->license}\n\n";
		$wiki .= "=== $description ===\n\n".str_replace("\n", "\n\n", trim($this->description))."\n\n";
		$wiki .= "=== $files ===\n\n{{Special:Transclude/modules/projects/index/filelist/{$this->id}/}}\n\n";
		$wiki .= "[[Category:Projects|{$this->name}]]\n";
		
		return $wiki;
	}
}

?>