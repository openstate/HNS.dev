<?php

require_once('record/Record.abstract.php');

class Issue extends Record {
	protected $tableName = 'iss_issues';

	protected $config = array(
		'id' => array(),
		'title' => array(),
		'description' => array(),
		'category' => array(),
		'priority' => array(),
		'status' => array(),
		'url' => array(),
		'user_id' => array(),
		'to_new' => array(),
		'to_progress' => array(),
		'to_closed' => array(),
	);
	
	protected $hasOneConfig = array(
		'user' => array(
			'class' => 'User',
			'local' => 'user_id',
			'foreign' => 'user_id',
		),
	);
	
	public function getWikiTitle() {
		return 'Issue:'.$this->id.' '.preg_replace('![]#<>|{}/?+[]!', '', $this->title);
	}
	
	public function getWikiContent() {
		$strings = array(
			'submitted_by', 'talk', 'contribs', 'category', 'priority', 'url', 'status',
			'to_new', 'to_progress', 'to_closed', 'description', 'time_format', 'progress', 'close');

		$tr = new GettextPO(dirname(__FILE__).'/../locales/en/issues.po');
		foreach ($strings as $s)
			$$s = $tr->getMsgstr('issue.'.$s);
		
		$cat = $tr->getMsgstr('issue.category.'.$this->category);
		$pri = $tr->getMsgstr('issue.priority.'.$this->priority);
		$sta = $tr->getMsgstr('issue.status.'.$this->status);
		
		$user = $this->user->user_name;

		$wiki = '';
		if ($this->status != 'closed') {
			$wiki .= "<guard group=\"sysop\"><div style=\"float: right;\">&#91;";
			if ($this->status == 'new')
				$wiki .= "[[Redirect:/modules/issues/index/progress/{$this->id}/|$progress]]] &#91;";
			$wiki .= "[[Redirect:/modules/issues/index/close/{$this->id}/|$close]]";
			$wiki .= "]</div>\n\n</guard>";
		}
		$wiki .= "''$submitted_by [[User:$user|$user]] ([[User talk:$user|$talk]] • [[Special:Contributions/$user|$contribs]])''\n\n";
		$wiki .= "* '''$category''': $cat\n* '''$priority''': {$this->priority}. $pri\n* '''$url''': {$this->url}\n* '''$status''': $sta\n";
		$wiki .= "* '''$to_new''': ".strftime($time_format, strtotime($this->to_new))."\n";
		if ($this->to_progress) $wiki .= "* '''$to_progress''': ".strftime($time_format, strtotime($this->to_progress))."\n";
		if ($this->to_closed) $wiki .= "* '''$to_closed''': ".strftime($time_format, strtotime($this->to_closed))."\n";
		$wiki .= "\n=== $description ===\n\n".str_replace("\n", "\n\n", trim($this->description))."\n\n";
		$wiki .= "[[Category:Issues|{$this->title}]]";
		if ($this->status == 'new') $wiki .= "[[Category:New issues|{$this->title}]]";
		if ($this->status != 'closed') $wiki .= "[[Category:Open issues|{$this->title}]]";
		$wiki .= "\n";
	
		return $wiki;
	}
}

?>